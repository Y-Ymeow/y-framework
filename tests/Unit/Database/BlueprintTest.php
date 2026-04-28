<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Framework\Database\Schema\Blueprint;

class BlueprintTest extends TestCase
{
    public function test_create_basic_table(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $blueprint->string('name');
        $blueprint->string('email');
        $blueprint->timestamps();

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('`id` BIGINT UNSIGNED', $sql);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('`name` VARCHAR(255)', $sql);
        $this->assertStringContainsString('`email` VARCHAR(255)', $sql);
        $this->assertStringContainsString('`created_at` TIMESTAMP', $sql);
        $this->assertStringContainsString('`updated_at` TIMESTAMP', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);
    }

    public function test_add_unique_index(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('email')->unique();

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('UNIQUE KEY', $sql);
        $this->assertStringContainsString('idx_users_email_unique', $sql);
    }

    public function test_add_foreign_key(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->id();
        $blueprint->bigInteger('user_id')->unsigned();
        $blueprint->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('FOREIGN KEY', $sql);
        $this->assertStringContainsString('REFERENCES `users`', $sql);
        $this->assertStringContainsString('ON DELETE CASCADE', $sql);
    }

    public function test_nullable_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('nickname')->nullable();

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`nickname` VARCHAR(255) NULL', $sql);
    }

    public function test_column_with_default(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('is_active')->default(true);

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('DEFAULT 1', $sql);
    }

    public function test_text_columns(): void
    {
        $blueprint = new Blueprint('articles');
        $blueprint->id();
        $blueprint->text('summary');
        $blueprint->longText('content');

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`summary` TEXT', $sql);
        $this->assertStringContainsString('`content` LONGTEXT', $sql);
    }

    public function test_numeric_columns(): void
    {
        $blueprint = new Blueprint('products');
        $blueprint->id();
        $blueprint->integer('stock');
        $blueprint->decimal('price', 10, 2);
        $blueprint->float('weight', 8, 3);

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`stock` INT', $sql);
        $this->assertStringContainsString('`price` DECIMAL(10, 2)', $sql);
        $this->assertStringContainsString('`weight` FLOAT(8, 3)', $sql);
    }

    public function test_date_columns(): void
    {
        $blueprint = new Blueprint('events');
        $blueprint->id();
        $blueprint->date('event_date');
        $blueprint->datetime('start_time');
        $blueprint->timestamp('published_at');

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`event_date` DATE', $sql);
        $this->assertStringContainsString('`start_time` DATETIME', $sql);
        $this->assertStringContainsString('`published_at` TIMESTAMP', $sql);
    }

    public function test_json_column(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('settings');

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`settings` JSON', $sql);
    }

    public function test_enum_column(): void
    {
        $blueprint = new Blueprint('orders');
        $blueprint->enum('status', ['pending', 'processing', 'completed', 'cancelled']);

        $sql = $blueprint->toSql();

        $this->assertStringContainsString("ENUM('pending', 'processing', 'completed', 'cancelled')", $sql);
    }

    public function test_soft_deletes(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $blueprint->softDeletes();

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`deleted_at` TIMESTAMP', $sql);
    }

    public function test_remember_token(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->rememberToken();

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`remember_token` VARCHAR(100)', $sql);
    }

    public function test_drop_table_sql(): void
    {
        $blueprint = new Blueprint('users');
        $sql = $blueprint->toDropSql();

        $this->assertEquals('DROP TABLE IF EXISTS `users`', $sql);
    }

    public function test_uuid_column(): void
    {
        $blueprint = new Blueprint('items');
        $blueprint->uuid('uuid');

        $sql = $blueprint->toSql();

        $this->assertStringContainsString('`uuid` CHAR(36)', $sql);
    }
}
