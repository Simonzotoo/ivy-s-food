<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_type_id',
        'default_sale_type_id',
        'name',
        'contact_person',
        'phone',
        'normalized_phone',
        'email',
        'location',
        'address',
        'status',
        'credit_limit',
        'outstanding_balance',
        'communication_consent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'communication_consent' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function defaultSaleType(): BelongsTo
    {
        return $this->belongsTo(SaleType::class, 'default_sale_type_id');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(CustomerCommunication::class);
    }
}
