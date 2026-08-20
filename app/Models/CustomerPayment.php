<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPayment extends Model
{
    protected $fillable = ['payment_number', 'customer_id', 'payment_method', 'amount', 'unallocated_amount', 'momo_network', 'transaction_reference', 'received_at', 'received_by'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'unallocated_amount' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
