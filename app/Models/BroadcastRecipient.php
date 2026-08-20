<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRecipient extends Model
{
    protected $fillable = [
        'broadcast_id',
        'customer_id',
        'status',
        'skip_reason',
        'provider_payload',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
