<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductCatalogueService
{
    public function __construct(private readonly AuditService $audit) {}

    public function updateVariant(ProductVariant $variant, array $data, User $actor): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data, $actor): ProductVariant {
            $old = $variant->only($this->auditedFields());

            $variant->fill($this->normalise($data));
            $this->guardActivation($variant);
            $variant->save();

            $this->audit->record('catalogue.variant_updated', $variant, $old, $variant->fresh()->only($this->auditedFields()), actor: $actor);

            return $variant->fresh(['category', 'type', 'size']);
        });
    }

    public function setVariantStatus(ProductVariant $variant, string $status, User $actor): void
    {
        DB::transaction(function () use ($variant, $status, $actor): void {
            $old = $variant->only(['status']);
            $variant->status = $status;
            $this->guardActivation($variant);
            $variant->save();

            $this->audit->record('catalogue.variant_status_changed', $variant, $old, ['status' => $variant->status], actor: $actor);
        });
    }

    private function guardActivation(ProductVariant $variant): void
    {
        if ($variant->status !== 'active') {
            return;
        }

        if (! $variant->isCompleteForActivation()) {
            throw ValidationException::withMessages([
                'status' => 'A variant needs SKU and all three prices before it can be activated.',
            ]);
        }
    }

    private function normalise(array $data): array
    {
        foreach (['sku', 'barcode', 'image_path'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        foreach (['retail_price', 'wholesale_price', 'distributor_price', 'reorder_level', 'shelf_life_days'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function auditedFields(): array
    {
        return [
            'name',
            'sku',
            'barcode',
            'retail_price',
            'wholesale_price',
            'distributor_price',
            'image_path',
            'status',
            'reorder_level',
            'shelf_life_days',
        ];
    }
}
