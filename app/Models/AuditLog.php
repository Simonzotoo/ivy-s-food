<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    /**
     * A short, human-readable one-line summary of what changed, for the audit log
     * list view. The full old/new payload remains available separately for anyone
     * who needs the raw detail.
     */
    public function summaryLine(): string
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        if (empty($old) && empty($new)) {
            return 'No field-level changes recorded.';
        }

        if (empty($old)) {
            $identifier = $new['name'] ?? $new['sale_number'] ?? $new['invoice_number'] ?? $new['payment_number'] ?? $new['expense_number'] ?? null;

            return $identifier ? 'Created: '.Str::limit((string) $identifier, 40) : 'New record created.';
        }

        $ignoredKeys = ['created_at', 'updated_at'];
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            if (in_array($key, $ignoredKeys, true)) {
                continue;
            }

            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = is_scalar($oldValue) && is_scalar($newValue)
                ? $key.': "'.Str::limit((string) $oldValue, 30).'" → "'.Str::limit((string) $newValue, 30).'"'
                : $key.' updated';
        }

        if (empty($changes)) {
            return 'No changes detected.';
        }

        $shown = array_slice($changes, 0, 3);
        $remaining = count($changes) - count($shown);

        return implode(', ', $shown).($remaining > 0 ? ', +'.$remaining.' more' : '');
    }
}
