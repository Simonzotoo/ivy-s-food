<?php

namespace App\Services;

use App\Models\MaterialBatch;
use App\Models\MaterialStockMovement;
use App\Models\ProductionBatch;
use App\Models\ProductionPlan;
use App\Models\Recipe;
use App\Models\UnitConversion;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeProductionService
{
    public function __construct(private readonly AuditService $audit) {}

    public function createRecipe(array $data, User $actor): Recipe
    {
        return DB::transaction(function () use ($data, $actor): Recipe {
            $version = (Recipe::query()->where('product_variant_id', $data['product_variant_id'])->max('version') ?? 0) + 1;

            $recipe = Recipe::query()->create([
                'product_variant_id' => $data['product_variant_id'],
                'version' => $version,
                'status' => 'draft',
                'expected_output_quantity' => $data['expected_output_quantity'] ?? null,
                'output_unit_id' => $data['output_unit_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['materials'] as $item) {
                $recipe->materials()->create([
                    'material_id' => $item['material_id'],
                    'unit_id' => $item['unit_id'],
                    'usage_type' => $item['usage_type'],
                    'quantity' => BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::HalfUp),
                    'wastage_allowance_percent' => BigDecimal::of((string) ($item['wastage_allowance_percent'] ?? 0))->toScale(2, RoundingMode::HalfUp),
                ]);
            }

            $this->audit->record('recipe.created', $recipe, [], $recipe->load('materials')->toArray(), actor: $actor);

            return $recipe->fresh(['productVariant', 'materials.material', 'materials.unit']);
        });
    }

    public function approveRecipe(Recipe $recipe, User $actor): Recipe
    {
        return DB::transaction(function () use ($recipe, $actor): Recipe {
            if ($recipe->materials()->count() === 0) {
                throw ValidationException::withMessages(['recipe' => 'A recipe must have at least one material before approval.']);
            }

            $old = $recipe->only(['status', 'approved_by', 'approved_at']);
            $recipe->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->record('recipe.approved', $recipe, $old, $recipe->only(['status', 'approved_by', 'approved_at']), actor: $actor);

            return $recipe->fresh(['materials']);
        });
    }

    public function createPlan(array $data, User $actor): ProductionPlan
    {
        return DB::transaction(function () use ($data, $actor): ProductionPlan {
            $plan = ProductionPlan::query()->create([
                'product_variant_id' => $data['product_variant_id'],
                'recipe_id' => $data['recipe_id'] ?? null,
                'planned_date' => $data['planned_date'],
                'planned_quantity' => BigDecimal::of((string) $data['planned_quantity'])->toScale(3, RoundingMode::HalfUp),
                'status' => 'planned',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->audit->record('production_plan.created', $plan, [], $plan->toArray(), actor: $actor);

            return $plan->fresh(['productVariant', 'recipe']);
        });
    }

    public function createBatch(array $data, User $actor): ProductionBatch
    {
        return DB::transaction(function () use ($data, $actor): ProductionBatch {
            $recipe = Recipe::query()->findOrFail($data['recipe_id']);
            if (! $recipe->isApproved()) {
                throw ValidationException::withMessages(['recipe_id' => 'Production batches require an approved recipe.']);
            }

            $batch = ProductionBatch::query()->create([
                'batch_number' => $this->nextBatchNumber(),
                'product_variant_id' => $recipe->product_variant_id,
                'recipe_id' => $recipe->id,
                'production_plan_id' => $data['production_plan_id'] ?? null,
                'production_date' => $data['production_date'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'planned_quantity' => BigDecimal::of((string) $data['planned_quantity'])->toScale(3, RoundingMode::HalfUp),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->audit->record('production_batch.created', $batch, [], $batch->toArray(), actor: $actor);

            return $batch->fresh(['productVariant', 'recipe']);
        });
    }

    public function completeBatch(ProductionBatch $batch, array $data, User $actor): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $data, $actor): ProductionBatch {
            if ($batch->status === 'completed') {
                throw ValidationException::withMessages(['batch' => 'This production batch has already been completed.']);
            }

            if (! $batch->recipe->isApproved()) {
                throw ValidationException::withMessages(['recipe' => 'Production cannot be completed without an approved recipe.']);
            }

            $recipe = $batch->recipe->load('materials.material.baseUnit');
            $scale = $this->recipeScale($recipe, BigDecimal::of((string) $batch->planned_quantity));
            $overrideUsages = collect($data['usages'] ?? [])->keyBy('recipe_material_id');
            $totalCost = BigDecimal::zero();

            foreach ($recipe->materials as $recipeMaterial) {
                $plannedRecipeUnits = BigDecimal::of((string) $recipeMaterial->quantity)->multipliedBy($scale)->toScale(3, RoundingMode::HalfUp);
                $actualRecipeUnits = BigDecimal::of((string) ($overrideUsages->get($recipeMaterial->id)['actual_quantity'] ?? $plannedRecipeUnits))->toScale(3, RoundingMode::HalfUp);
                $remainingRecipeUnits = $actualRecipeUnits;

                $batches = MaterialBatch::query()
                    ->where('material_id', $recipeMaterial->material_id)
                    ->where('status', 'available')
                    ->where('quantity_available', '>', 0)
                    ->with('purchaseItem')
                    ->orderByRaw('expiry_date IS NULL, expiry_date asc')
                    ->orderBy('received_date')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $materialBatch) {
                    if ($remainingRecipeUnits->isZero()) {
                        break;
                    }

                    $stockUnitId = $materialBatch->purchaseItem?->unit_id ?? $recipeMaterial->material->base_unit_id;
                    $remainingStockUnits = $this->convert($remainingRecipeUnits, $recipeMaterial->unit_id, $stockUnitId);
                    $availableStockUnits = BigDecimal::of((string) $materialBatch->quantity_available);
                    $consumeStockUnits = $remainingStockUnits->isLessThanOrEqualTo($availableStockUnits) ? $remainingStockUnits : $availableStockUnits;
                    $consumeRecipeUnits = $this->convert($consumeStockUnits, $stockUnitId, $recipeMaterial->unit_id);

                    $unitCost = BigDecimal::of((string) ($materialBatch->unit_cost ?? 0))->toScale(4, RoundingMode::HalfUp);
                    $lineCost = $consumeStockUnits->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp);
                    $totalCost = $totalCost->plus($lineCost);

                    $materialBatch->update([
                        'quantity_available' => $availableStockUnits->minus($consumeStockUnits)->toScale(3, RoundingMode::HalfUp),
                    ]);

                    $usage = $batch->usages()->create([
                        'material_id' => $recipeMaterial->material_id,
                        'material_batch_id' => $materialBatch->id,
                        'unit_id' => $stockUnitId,
                        'usage_type' => $recipeMaterial->usage_type,
                        'planned_quantity' => $this->convert($plannedRecipeUnits, $recipeMaterial->unit_id, $stockUnitId)->toScale(3, RoundingMode::HalfUp),
                        'actual_quantity' => $consumeStockUnits->toScale(3, RoundingMode::HalfUp),
                        'unit_cost' => $unitCost,
                        'line_cost' => $lineCost,
                    ]);

                    MaterialStockMovement::query()->create([
                        'material_id' => $recipeMaterial->material_id,
                        'material_batch_id' => $materialBatch->id,
                        'unit_id' => $stockUnitId,
                        'movement_type' => 'production_usage',
                        'quantity' => $consumeStockUnits->negated()->toScale(3, RoundingMode::HalfUp),
                        'unit_cost' => $unitCost,
                        'reference_type' => $usage::class,
                        'reference_id' => $usage->id,
                        'created_by' => $actor->id,
                        'occurred_at' => now(),
                        'notes' => 'Production usage for '.$batch->batch_number,
                    ]);

                    $remainingRecipeUnits = $remainingRecipeUnits->minus($consumeRecipeUnits)->toScale(3, RoundingMode::HalfUp);
                }

                if ($remainingRecipeUnits->isGreaterThan(BigDecimal::zero())) {
                    throw ValidationException::withMessages([
                        'materials' => 'Insufficient stock for '.$recipeMaterial->material->name.'.',
                    ]);
                }
            }

            $actualOutput = BigDecimal::of((string) $data['actual_output_quantity'])->toScale(3, RoundingMode::HalfUp);
            $rejected = BigDecimal::of((string) ($data['rejected_quantity'] ?? 0))->toScale(3, RoundingMode::HalfUp);
            $wastage = BigDecimal::of((string) ($data['wastage_quantity'] ?? 0))->toScale(3, RoundingMode::HalfUp);
            $unitCost = $actualOutput->isZero() ? null : $totalCost->dividedBy($actualOutput, 4, RoundingMode::HalfUp);
            $yield = BigDecimal::of((string) $batch->planned_quantity)->isZero()
                ? null
                : $actualOutput->dividedBy((string) $batch->planned_quantity, 4, RoundingMode::HalfUp)->multipliedBy('100')->toScale(2, RoundingMode::HalfUp);

            $old = $batch->only(['status']);
            $batch->update([
                'status' => 'completed',
                'actual_output_quantity' => $actualOutput,
                'rejected_quantity' => $rejected,
                'wastage_quantity' => $wastage,
                'yield_percent' => $yield,
                'estimated_batch_cost' => $totalCost->toScale(2, RoundingMode::HalfUp),
                'estimated_unit_cost' => $unitCost,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $batch->output()->create([
                'product_variant_id' => $batch->product_variant_id,
                'accepted_quantity' => $actualOutput,
                'rejected_quantity' => $rejected,
                'estimated_unit_cost' => $unitCost,
                'status' => 'pending_inventory',
            ]);

            $this->audit->record('production_batch.completed', $batch, $old, $batch->fresh(['usages', 'output'])->toArray(), actor: $actor);

            return $batch->fresh(['productVariant', 'recipe', 'usages.material', 'output']);
        });
    }

    public function reverseBatch(ProductionBatch $batch, array $data, User $actor): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $data, $actor): ProductionBatch {
            if ($batch->status !== 'completed') {
                throw ValidationException::withMessages(['batch' => 'Only completed production batches can be reversed.']);
            }

            foreach ($batch->usages()->with('materialBatch')->lockForUpdate()->get() as $usage) {
                if ($usage->materialBatch) {
                    $available = BigDecimal::of((string) $usage->materialBatch->quantity_available);
                    $usage->materialBatch->update([
                        'quantity_available' => $available->plus((string) $usage->actual_quantity)->toScale(3, RoundingMode::HalfUp),
                    ]);
                }

                MaterialStockMovement::query()->create([
                    'material_id' => $usage->material_id,
                    'material_batch_id' => $usage->material_batch_id,
                    'unit_id' => $usage->unit_id,
                    'movement_type' => 'production_reversal',
                    'quantity' => $usage->actual_quantity,
                    'unit_cost' => $usage->unit_cost,
                    'reference_type' => $batch::class,
                    'reference_id' => $batch->id,
                    'created_by' => $actor->id,
                    'occurred_at' => now(),
                    'notes' => $data['reason'],
                ]);
            }

            $old = $batch->only(['status']);
            $batch->update([
                'status' => 'reversed',
                'reversal_reason' => $data['reason'],
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
            ]);
            $batch->output?->update(['status' => 'reversed']);

            $this->audit->record('production_batch.reversed', $batch, $old, $batch->fresh(['usages', 'output'])->toArray(), actor: $actor);

            return $batch->fresh(['usages', 'output']);
        });
    }

    private function recipeScale(Recipe $recipe, BigDecimal $plannedOutput): BigDecimal
    {
        if ($recipe->expected_output_quantity === null || BigDecimal::of((string) $recipe->expected_output_quantity)->isZero()) {
            return BigDecimal::one();
        }

        return $plannedOutput->dividedBy((string) $recipe->expected_output_quantity, 6, RoundingMode::HalfUp);
    }

    private function convert(BigDecimal $quantity, int $fromUnitId, int $toUnitId): BigDecimal
    {
        if ($fromUnitId === $toUnitId) {
            return $quantity->toScale(3, RoundingMode::HalfUp);
        }

        $conversion = UnitConversion::query()->where('from_unit_id', $fromUnitId)->where('to_unit_id', $toUnitId)->first();
        if ($conversion) {
            return $quantity->multipliedBy((string) $conversion->factor)->toScale(3, RoundingMode::HalfUp);
        }

        $inverse = UnitConversion::query()->where('from_unit_id', $toUnitId)->where('to_unit_id', $fromUnitId)->first();
        if ($inverse) {
            return $quantity->dividedBy((string) $inverse->factor, 3, RoundingMode::HalfUp);
        }

        throw ValidationException::withMessages(['unit' => 'A required unit conversion is missing.']);
    }

    private function nextBatchNumber(): string
    {
        return 'PROD-'.now()->format('Ymd').'-'.str_pad((string) (ProductionBatch::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
