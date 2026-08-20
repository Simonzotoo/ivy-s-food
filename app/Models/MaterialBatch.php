<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialBatch extends Model
{
    protected $fillable = [
        'material_id',
        'supplier_id',
        'purchase_item_id',
        'lot_number',
        'received_date',
        'expiry_date',
        'quantity_received',
        'quantity_available',
        'unit_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'expiry_date' => 'date',
            'quantity_received' => 'decimal:3',
            'quantity_available' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
