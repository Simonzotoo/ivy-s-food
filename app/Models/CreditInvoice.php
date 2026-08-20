<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditInvoice extends Model
{
    protected $fillable = ['invoice_number', 'sale_id', 'customer_id', 'issue_date', 'due_date', 'total', 'balance', 'status'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
