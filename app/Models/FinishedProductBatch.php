<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishedProductBatch extends Model
{
    protected $fillable = [
        'product_variant_id',
        'production_batch_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'quantity_received',
        'quantity_available',
        'quantity_reserved',
        'quantity_damaged',
        'quantity_expired',
        'quantity_quarantined',
        'unit_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'quantity_received' => 'decimal:3',
            'quantity_available' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'quantity_damaged' => 'decimal:3',
            'quantity_expired' => 'decimal:3',
            'quantity_quarantined' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinishedStockMovement::class);
    }
}
