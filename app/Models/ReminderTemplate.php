<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderTemplate extends Model
{
    protected $fillable = ['name', 'channel', 'trigger_status', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
