<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    protected $fillable = ['name', 'slug', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }
}
