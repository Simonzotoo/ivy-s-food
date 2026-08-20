<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOutput extends Model
{
    protected $fillable = [
        'production_batch_id',
        'product_variant_id',
        'accepted_quantity',
        'rejected_quantity',
        'estimated_unit_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'accepted_quantity' => 'decimal:3',
            'rejected_quantity' => 'decimal:3',
            'estimated_unit_cost' => 'decimal:4',
        ];
    }

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
