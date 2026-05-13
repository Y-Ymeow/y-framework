<?php

declare(strict_types=1);

namespace Shop\Models;

use Framework\Database\Model;

class Order extends Model
{
    protected string $table = 'shop_orders';
    protected array $fillable = [
        'order_number', 'user_id', 'customer_name', 'customer_email',
        'customer_phone', 'status', 'subtotal', 'discount', 'shipping_fee',
        'total', 'payment_method', 'payment_status', 'shipping_address',
        'notes',
    ];
    protected array $casts = [
        'user_id' => 'int',
        'subtotal' => 'float',
        'discount' => 'float',
        'shipping_fee' => 'float',
        'total' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => '待付款',
            'paid' => '已付款',
            'shipped' => '已发货',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'refunded' => '已退款',
            default => $this->status ?? '未知',
        };
    }
}

class OrderItem extends Model
{
    protected string $table = 'shop_order_items';
    protected array $fillable = [
        'order_id', 'product_id', 'product_name', 'product_image',
        'price', 'quantity', 'subtotal',
    ];
    protected array $casts = [
        'order_id' => 'int',
        'product_id' => 'int',
        'price' => 'float',
        'quantity' => 'int',
        'subtotal' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}