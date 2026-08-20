<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemBatch extends Model
{
    protected $fillable = ['sale_item_id', 'finished_product_batch_id', 'quantity', 'unit_cost'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function finishedProductBatch(): BelongsTo
    {
        return $this->belongsTo(FinishedProductBatch::class);
    }
}
