<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeItem extends Model
{
    protected $fillable = [
        'stocktake_id',
        'material_batch_id',
        'finished_product_batch_id',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:3',
            'counted_quantity' => 'decimal:3',
            'variance_quantity' => 'decimal:3',
        ];
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }

    public function materialBatch(): BelongsTo
    {
        return $this->belongsTo(MaterialBatch::class);
    }

    public function finishedProductBatch(): BelongsTo
    {
        return $this->belongsTo(FinishedProductBatch::class);
    }
}
