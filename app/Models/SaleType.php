<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleType extends Model
{
    protected $fillable = ['name', 'slug', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }
}
