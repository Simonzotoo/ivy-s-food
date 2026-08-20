<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeMaterial extends Model
{
    protected $fillable = [
        'recipe_id',
        'material_id',
        'unit_id',
        'usage_type',
        'quantity',
        'wastage_allowance_percent',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'wastage_allowance_percent' => 'decimal:2',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }
}
