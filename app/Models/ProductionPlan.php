<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlan extends Model
{
    protected $fillable = [
        'product_variant_id',
        'recipe_id',
        'planned_date',
        'planned_quantity',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'planned_quantity' => 'decimal:3',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
