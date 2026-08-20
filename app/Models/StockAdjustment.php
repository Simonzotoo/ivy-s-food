<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    protected $fillable = [
        'stock_type',
        'material_batch_id',
        'finished_product_batch_id',
        'adjustment_type',
        'quantity',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
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
