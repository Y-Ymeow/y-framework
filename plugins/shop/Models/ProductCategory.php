<?php

declare(strict_types=1);

namespace Shop\Models;

use Framework\Database\Model;

class ProductCategory extends Model
{
    protected string $table = 'shop_categories';
    protected array $fillable = [
        'name', 'slug', 'description', 'image',
        'parent_id', 'sort_order',
    ];
    protected array $casts = [
        'parent_id' => 'int',
        'sort_order' => 'int',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}