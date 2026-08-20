<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'material_category_id',
        'base_unit_id',
        'name',
        'sku',
        'status',
        'track_lot',
        'track_expiry',
        'reorder_level',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'track_lot' => 'boolean',
            'track_expiry' => 'boolean',
            'reorder_level' => 'decimal:3',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'base_unit_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MaterialBatch::class);
    }

    public function availableQuantity(): string
    {
        return (string) $this->batches()->sum('quantity_available');
    }
}
