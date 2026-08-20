<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = ['batch_number', 'import_type', 'status', 'dry_run', 'total_rows', 'valid_rows', 'error_rows', 'summary', 'rollback_metadata', 'created_by', 'applied_at'];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'summary' => 'array',
            'rollback_metadata' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
