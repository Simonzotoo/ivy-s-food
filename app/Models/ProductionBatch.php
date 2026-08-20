<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'product_variant_id',
        'recipe_id',
        'production_plan_id',
        'status',
        'production_date',
        'expiry_date',
        'planned_quantity',
        'actual_output_quantity',
        'rejected_quantity',
        'wastage_quantity',
        'yield_percent',
        'estimated_batch_cost',
        'estimated_unit_cost',
        'notes',
        'reversal_reason',
        'created_by',
        'completed_by',
        'completed_at',
        'reversed_by',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'planned_quantity' => 'decimal:3',
            'actual_output_quantity' => 'decimal:3',
            'rejected_quantity' => 'decimal:3',
            'wastage_quantity' => 'decimal:3',
            'yield_percent' => 'decimal:2',
            'estimated_batch_cost' => 'decimal:2',
            'estimated_unit_cost' => 'decimal:4',
            'completed_at' => 'datetime',
            'reversed_at' => 'datetime',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class, 'production_plan_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ProductionMaterialUsage::class);
    }

    public function output(): HasOne
    {
        return $this->hasOne(ProductionOutput::class);
    }
}
