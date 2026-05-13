<?php

declare(strict_types=1);

namespace Shop\Models;

use Framework\Database\Model;

class Product extends Model
{
    protected string $table = 'shop_products';
    protected array $fillable = [
        'name', 'slug', 'description', 'price', 'compare_price',
        'cost_price', 'sku', 'barcode', 'stock_quantity', 'weight',
        'status', 'category_id', 'images', 'sort_order',
    ];
    protected array $casts = [
        'price' => 'float',
        'compare_price' => 'float',
        'cost_price' => 'float',
        'stock_quantity' => 'int',
        'weight' => 'float',
        'sort_order' => 'int',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function getImagesArray(): array
    {
        $json = $this->images;
        if (empty($json)) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getPrimaryImage(): string
    {
        $images = $this->getImagesArray();
        return $images[0] ?? '';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getFormattedPrice(): string
    {
        return number_format((float)$this->price, 2);
    }
}