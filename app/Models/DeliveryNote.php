<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNote extends Model
{
    protected $fillable = ['note_number', 'sale_id', 'delivery_address', 'status'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
