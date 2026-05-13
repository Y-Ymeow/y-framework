<?php

declare(strict_types=1);

namespace Shop\Migrations;

use Framework\Database\Schema\Schema;
use Framework\Database\Connection\Manager;

class CreateShopTables
{
    public function __construct(private Manager $manager) {}

    public function up(): void
    {
        $schema = new Schema($this->manager);

        $schema->create('shop_categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('parent_id')->nullable()->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $schema->create('shop_products', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->decimal('weight', 8, 2)->default(0);
            $table->string('status')->default('draft');
            $table->integer('category_id')->nullable();
            $table->text('images')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category_id');
            $table->index('status');
        });

        $schema->create('shop_orders', function ($table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->integer('user_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('user_id');
            $table->index('status');
        });

        $schema->create('shop_order_items', function ($table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id')->nullable();
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        $schema = new Schema($this->manager);
        $schema->dropIfExists('shop_order_items');
        $schema->dropIfExists('shop_orders');
        $schema->dropIfExists('shop_products');
        $schema->dropIfExists('shop_categories');
    }
}