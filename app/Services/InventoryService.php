<?php

namespace App\Services;

use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\MaterialBatch;
use App\Models\MaterialStockMovement;
use App\Models\ProductionOutput;
use App\Models\StockAdjustment;
use App\Models\Stocktake;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private readonly AuditService $audit) {}

    public function postProductionOutput(ProductionOutput $output, User $actor): FinishedProductBatch
    {
        return DB::transaction(function () use ($output, $actor): FinishedProductBatch {
            $output = $output->load('productionBatch');
            if ($output->status !== 'pending_inventory') {
                throw ValidationException::withMessages(['output' => 'This production output has already been posted or is unavailable.']);
            }

            $batch = FinishedProductBatch::query()->create([
                'product_variant_id' => $output->product_variant_id,
                'production_batch_id' => $output->production_batch_id,
                'batch_number' => $output->productionBatch->batch_number,
                'production_date' => $output->productionBatch->production_date,
                'expiry_date' => $output->productionBatch->expiry_date,
                'quantity_received' => $output->accepted_quantity,
                'quantity_available' => $output->accepted_quantity,
                'unit_cost' => $output->estimated_unit_cost,
                'status' => 'available',
            ]);

            $this->finishedMovement($batch, 'production_output', BigDecimal::of((string) $output->accepted_quantity), $output->estimated_unit_cost, $output, $actor, 'Posted finished production output.');
            $old = $output->only(['status']);
            $output->update(['status' => 'posted_to_inventory']);

            $this->audit->record('inventory.production_output_posted', $batch, $old, $batch->toArray(), actor: $actor);

            return $batch->fresh(['productVariant']);
        });
    }

    /**
     * @return Collection<int, FinishedProductBatch>
     */
    public function reserveFinishedStock(int $productVariantId, string $quantity, User $actor): Collection
    {
        return DB::transaction(function () use ($productVariantId, $quantity, $actor): Collection {
            $remaining = BigDecimal::of($quantity)->toScale(3, RoundingMode::HalfUp);
            $reserved = collect();

            $batches = FinishedProductBatch::query()
                ->where('product_variant_id', $productVariantId)
                ->where('status', 'available')
                ->where('quantity_available', '>', 0)
                ->orderByRaw('expiry_date IS NULL, expiry_date asc')
                ->orderBy('production_date')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining->isZero()) {
                    break;
                }

                $available = BigDecimal::of((string) $batch->quantity_available);
                $take = $remaining->isLessThanOrEqualTo($available) ? $remaining : $available;

                $batch->update([
                    'quantity_available' => $available->minus($take)->toScale(3, RoundingMode::HalfUp),
                    'quantity_reserved' => BigDecimal::of((string) $batch->quantity_reserved)->plus($take)->toScale(3, RoundingMode::HalfUp),
                ]);

                $this->finishedMovement($batch, 'finished_reserved', $take->negated(), $batch->unit_cost, null, $actor, 'Reserved by earliest-expiry-first allocation.');
                $remaining = $remaining->minus($take)->toScale(3, RoundingMode::HalfUp);
                $reserved->push($batch->fresh());
            }

            if ($remaining->isGreaterThan(BigDecimal::zero())) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient finished stock for allocation.']);
            }

            return $reserved;
        });
    }

    public function adjustRaw(MaterialBatch $batch, string $type, string $quantity, string $reason, User $actor): StockAdjustment
    {
        return DB::transaction(function () use ($batch, $type, $quantity, $reason, $actor): StockAdjustment {
            $qty = BigDecimal::of($quantity)->toScale(3, RoundingMode::HalfUp);
            $available = BigDecimal::of((string) $batch->quantity_available);
            $signed = $this->isIncrease($type) ? $qty : $qty->negated();

            if ($signed->isNegative() && $qty->isGreaterThan($available)) {
                throw ValidationException::withMessages(['quantity' => 'Adjustment cannot create negative raw-material stock.']);
            }

            $adjustment = StockAdjustment::query()->create([
                'stock_type' => 'raw',
                'material_batch_id' => $batch->id,
                'adjustment_type' => $type,
                'quantity' => $signed,
                'reason' => $reason,
                'created_by' => $actor->id,
            ]);

            $batch->update(['quantity_available' => $available->plus($signed)->toScale(3, RoundingMode::HalfUp)]);

            MaterialStockMovement::query()->create([
                'material_id' => $batch->material_id,
                'material_batch_id' => $batch->id,
                'unit_id' => $batch->purchaseItem?->unit_id ?? $batch->material->base_unit_id,
                'movement_type' => 'stock_adjustment',
                'quantity' => $signed,
                'unit_cost' => $batch->unit_cost,
                'reference_type' => $adjustment::class,
                'reference_id' => $adjustment->id,
                'created_by' => $actor->id,
                'occurred_at' => now(),
                'notes' => $reason,
            ]);

            $this->audit->record('inventory.raw_adjusted', $adjustment, [], $adjustment->toArray(), actor: $actor);

            return $adjustment;
        });
    }

    public function adjustFinished(FinishedProductBatch $batch, string $type, string $quantity, string $reason, User $actor): StockAdjustment
    {
        return DB::transaction(function () use ($batch, $type, $quantity, $reason, $actor): StockAdjustment {
            $qty = BigDecimal::of($quantity)->toScale(3, RoundingMode::HalfUp);
            $available = BigDecimal::of((string) $batch->quantity_available);
            $signed = $this->isIncrease($type) || $type === 'release_quarantine' ? $qty : $qty->negated();

            if ($type === 'release_quarantine') {
                $quarantined = BigDecimal::of((string) $batch->quantity_quarantined);
                if ($qty->isGreaterThan($quarantined)) {
                    throw ValidationException::withMessages(['quantity' => 'Release quantity cannot exceed quarantined stock.']);
                }

                $batch->update([
                    'quantity_quarantined' => $quarantined->minus($qty)->toScale(3, RoundingMode::HalfUp),
                    'quantity_available' => $available->plus($qty)->toScale(3, RoundingMode::HalfUp),
                ]);
            } else {
                if ($signed->isNegative() && $qty->isGreaterThan($available)) {
                    throw ValidationException::withMessages(['quantity' => 'Adjustment cannot create negative finished-product stock.']);
                }

                $updates = ['quantity_available' => $available->plus($signed)->toScale(3, RoundingMode::HalfUp)];
                $bucket = $this->finishedBucketColumn($type);
                if ($bucket) {
                    $updates[$bucket] = BigDecimal::of((string) $batch->{$bucket})->plus($qty)->toScale(3, RoundingMode::HalfUp);
                }

                $batch->update($updates);
            }

            $adjustment = StockAdjustment::query()->create([
                'stock_type' => 'finished',
                'finished_product_batch_id' => $batch->id,
                'adjustment_type' => $type,
                'quantity' => $signed,
                'reason' => $reason,
                'created_by' => $actor->id,
            ]);

            $this->finishedMovement($batch->fresh(), 'stock_adjustment', $signed, $batch->unit_cost, $adjustment, $actor, $reason);
            $this->audit->record('inventory.finished_adjusted', $adjustment, [], $adjustment->toArray(), actor: $actor);

            return $adjustment;
        });
    }

    public function createStocktake(array $data, User $actor): Stocktake
    {
        return DB::transaction(function () use ($data, $actor): Stocktake {
            $stocktake = Stocktake::query()->create([
                'stock_type' => $data['stock_type'],
                'stocktake_date' => $data['stocktake_date'],
                'status' => 'pending_review',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                $batch = $data['stock_type'] === 'raw'
                    ? MaterialBatch::query()->findOrFail($item['batch_id'])
                    : FinishedProductBatch::query()->findOrFail($item['batch_id']);
                $system = BigDecimal::of((string) $batch->quantity_available)->toScale(3, RoundingMode::HalfUp);
                $counted = BigDecimal::of((string) $item['counted_quantity'])->toScale(3, RoundingMode::HalfUp);

                $stocktake->items()->create([
                    'material_batch_id' => $data['stock_type'] === 'raw' ? $batch->id : null,
                    'finished_product_batch_id' => $data['stock_type'] === 'finished' ? $batch->id : null,
                    'system_quantity' => $system,
                    'counted_quantity' => $counted,
                    'variance_quantity' => $counted->minus($system)->toScale(3, RoundingMode::HalfUp),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->audit->record('inventory.stocktake_created', $stocktake, [], $stocktake->load('items')->toArray(), actor: $actor);

            return $stocktake->fresh('items');
        });
    }

    public function approveStocktake(Stocktake $stocktake, User $actor): Stocktake
    {
        return DB::transaction(function () use ($stocktake, $actor): Stocktake {
            if ($stocktake->status !== 'pending_review') {
                throw ValidationException::withMessages(['stocktake' => 'Only pending stocktakes can be approved.']);
            }

            foreach ($stocktake->items as $item) {
                $variance = BigDecimal::of((string) $item->variance_quantity);
                if ($variance->isZero()) {
                    continue;
                }

                if ($stocktake->stock_type === 'raw') {
                    $this->adjustRaw($item->materialBatch, $variance->isPositive() ? 'increase' : 'decrease', (string) $variance->abs(), 'Stocktake variance #'.$stocktake->id, $actor);
                } else {
                    $this->adjustFinished($item->finishedProductBatch, $variance->isPositive() ? 'increase' : 'decrease', (string) $variance->abs(), 'Stocktake variance #'.$stocktake->id, $actor);
                }
            }

            $old = $stocktake->only(['status']);
            $stocktake->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->record('inventory.stocktake_approved', $stocktake, $old, $stocktake->fresh('items')->toArray(), actor: $actor);

            return $stocktake->fresh('items');
        });
    }

    private function finishedMovement(FinishedProductBatch $batch, string $type, BigDecimal $quantity, mixed $unitCost, mixed $reference, User $actor, string $notes): void
    {
        FinishedStockMovement::query()->create([
            'product_variant_id' => $batch->product_variant_id,
            'finished_product_batch_id' => $batch->id,
            'movement_type' => $type,
            'quantity' => $quantity->toScale(3, RoundingMode::HalfUp),
            'unit_cost' => $unitCost,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'created_by' => $actor->id,
            'occurred_at' => now(),
            'notes' => $notes,
        ]);
    }

    private function isIncrease(string $type): bool
    {
        return in_array($type, ['increase', 'opening_balance'], true);
    }

    private function finishedBucketColumn(string $type): ?string
    {
        return match ($type) {
            'damage', 'spoilage' => 'quantity_damaged',
            'expired' => 'quantity_expired',
            'quarantine' => 'quantity_quarantined',
            default => null,
        };
    }
}
