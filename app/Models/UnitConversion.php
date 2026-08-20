<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    protected $fillable = ['from_unit_id', 'to_unit_id', 'factor'];

    protected function casts(): array
    {
        return ['factor' => 'decimal:6'];
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'to_unit_id');
    }
}
