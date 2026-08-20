<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'product_variant_id',
        'version',
        'status',
        'expected_output_quantity',
        'output_unit_id',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_output_quantity' => 'decimal:3',
            'approved_at' => 'datetime',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'output_unit_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(RecipeMaterial::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
