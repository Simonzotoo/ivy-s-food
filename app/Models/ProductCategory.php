<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    public function types(): HasMany
    {
        return $this->hasMany(ProductType::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }
}
