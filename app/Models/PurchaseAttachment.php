<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAttachment extends Model
{
    protected $fillable = ['purchase_id', 'original_name', 'path', 'mime_type', 'size_bytes', 'uploaded_by'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
