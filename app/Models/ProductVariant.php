<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_category_id',
        'product_type_id',
        'product_size_id',
        'name',
        'sku',
        'barcode',
        'retail_price',
        'wholesale_price',
        'distributor_price',
        'image_path',
        'status',
        'reorder_level',
        'shelf_life_days',
    ];

    protected function casts(): array
    {
        return [
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'distributor_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
            'shelf_life_days' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id');
    }

    public function isCompleteForActivation(): bool
    {
        return filled($this->sku)
            && $this->retail_price !== null
            && $this->wholesale_price !== null
            && $this->distributor_price !== null;
    }
}
