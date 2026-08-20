<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditReminder extends Model
{
    protected $fillable = ['credit_invoice_id', 'customer_id', 'reminder_template_id', 'channel', 'status', 'message', 'scheduled_for', 'sent_at', 'provider_payload', 'created_by'];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CreditInvoice::class, 'credit_invoice_id');
    }
}
