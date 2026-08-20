<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    protected $fillable = [
        'broadcast_number',
        'broadcast_template_id',
        'name',
        'channel',
        'subject',
        'body',
        'status',
        'filters',
        'scheduled_for',
        'sent_at',
        'total_recipients',
        'total_logged',
        'total_skipped',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BroadcastTemplate::class, 'broadcast_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class);
    }
}
