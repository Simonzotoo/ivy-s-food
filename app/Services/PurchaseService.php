<?php

namespace App\Services;

use App\Models\MaterialBatch;
use App\Models\MaterialStockMovement;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data, User $actor): Purchase
    {
        return DB::transaction(function () use ($data, $actor): Purchase {
            $purchase = Purchase::query()->create([
                'purchase_number' => $this->nextPurchaseNumber(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'subtotal' => '0.00',
            ]);

            $subtotal = BigDecimal::zero();
            foreach ($data['items'] as $item) {
                $quantity = BigDecimal::of((string) $item['quantity']);
                $unitCost = BigDecimal::of((string) $item['unit_cost']);
                $lineTotal = $quantity->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp);
                $subtotal = $subtotal->plus($lineTotal);

                $purchase->items()->create([
                    'material_id' => $item['material_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $quantity->toScale(3, RoundingMode::HalfUp),
                    'unit_cost' => $unitCost->toScale(4, RoundingMode::HalfUp),
                    'line_total' => $lineTotal,
                ]);
            }

            foreach ($data['attachments'] ?? [] as $attachment) {
                if (! filled($attachment['original_name'] ?? null) || ! filled($attachment['path'] ?? null)) {
                    continue;
                }

                $purchase->attachments()->create([
                    'original_name' => $attachment['original_name'],
                    'path' => $attachment['path'],
                    'uploaded_by' => $actor->id,
                ]);
            }

            $purchase->update(['subtotal' => $subtotal->toScale(2, RoundingMode::HalfUp)]);
            $this->audit->record('purchase.created', $purchase, [], $purchase->load(['items', 'attachments'])->toArray(), actor: $actor);

            return $purchase->fresh(['supplier', 'items.material', 'items.unit']);
        });
    }

    public function receive(Purchase $purchase, array $data, User $actor): void
    {
        DB::transaction(function () use ($purchase, $data, $actor): void {
            if ($purchase->status === 'received') {
                throw ValidationException::withMessages(['purchase' => 'This purchase has already been received.']);
            }

            $receivedDate = $data['received_date'];
            $batchData = collect($data['batches'] ?? [])->keyBy('purchase_item_id');

            foreach ($purchase->items()->with('material')->get() as $item) {
                $row = $batchData->get($item->id, []);
                $batch = MaterialBatch::query()->create([
                    'material_id' => $item->material_id,
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_item_id' => $item->id,
                    'lot_number' => $row['lot_number'] ?? null,
                    'received_date' => $receivedDate,
                    'expiry_date' => $row['expiry_date'] ?? null,
                    'quantity_received' => $item->quantity,
                    'quantity_available' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'status' => 'available',
                ]);

                MaterialStockMovement::query()->create([
                    'material_id' => $item->material_id,
                    'material_batch_id' => $batch->id,
                    'unit_id' => $item->unit_id,
                    'movement_type' => 'purchase_receive',
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => $purchase::class,
                    'reference_id' => $purchase->id,
                    'created_by' => $actor->id,
                    'occurred_at' => now(),
                    'notes' => 'Goods received from purchase '.$purchase->purchase_number,
                ]);
            }

            $old = $purchase->only(['status']);
            $purchase->update(['status' => 'received']);
            $this->audit->record('purchase.received', $purchase, $old, ['status' => 'received'], actor: $actor);
        });
    }

    public function returnBatch(MaterialBatch $batch, array $data, User $actor): PurchaseReturn
    {
        return DB::transaction(function () use ($batch, $data, $actor): PurchaseReturn {
            $quantity = BigDecimal::of((string) $data['quantity'])->toScale(3, RoundingMode::HalfUp);
            $available = BigDecimal::of((string) $batch->quantity_available);

            if ($quantity->isGreaterThan($available)) {
                throw ValidationException::withMessages(['quantity' => 'Return quantity cannot exceed available batch quantity.']);
            }

            $purchase = $batch->purchaseItem->purchase;
            $return = PurchaseReturn::query()->create([
                'return_number' => $this->nextReturnNumber(),
                'purchase_id' => $purchase->id,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'status' => 'completed',
                'created_by' => $actor->id,
            ]);

            $return->items()->create([
                'material_batch_id' => $batch->id,
                'quantity' => $quantity,
                'reason' => $data['reason'],
            ]);

            $batch->update([
                'quantity_available' => $available->minus($quantity)->toScale(3, RoundingMode::HalfUp),
            ]);

            MaterialStockMovement::query()->create([
                'material_id' => $batch->material_id,
                'material_batch_id' => $batch->id,
                'unit_id' => $batch->purchaseItem->unit_id,
                'movement_type' => 'purchase_return',
                'quantity' => $quantity->negated(),
                'unit_cost' => $batch->unit_cost,
                'reference_type' => $return::class,
                'reference_id' => $return->id,
                'created_by' => $actor->id,
                'occurred_at' => now(),
                'notes' => $data['reason'],
            ]);

            $this->audit->record('purchase.returned', $return, [], $return->load('items')->toArray(), actor: $actor);

            return $return;
        });
    }

    private function nextPurchaseNumber(): string
    {
        return 'PUR-'.now()->format('Ymd').'-'.str_pad((string) (Purchase::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextReturnNumber(): string
    {
        return 'PRET-'.now()->format('Ymd').'-'.str_pad((string) (PurchaseReturn::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
