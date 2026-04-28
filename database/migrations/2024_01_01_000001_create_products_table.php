<?php

declare(strict_types=1);

namespace Database\Migrations;

use Framework\Database\Migration\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('status')->default('active');
            $table->string('category')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->drop('products');
    }
}
