<?php

declare(strict_types=1);

namespace Framework\Database\Migrations;

/**
 * 迁移基类
 */
abstract class Migration
{
    /**
     * 运行迁移
     */
    abstract public function up(): void;

    /**
     * 回滚迁移
     */
    abstract public function down(): void;
}
