<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

use Closure;
use Framework\Database\DatabaseManager;

/**
 * 数据库结构定义入口
 */
final class Schema
{
    /**
     * 创建新表
     */
    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = $blueprint->toSql();
        self::getConnection()->execute($sql);
    }

    /**
     * 修改表结构 (待实现更多 Blueprint 方法)
     */
    public static function table(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        // 简单实现：这里应该是 ALTER TABLE 逻辑
        // 对于 SQLite，有些 ALTER 操作需要重建表
    }

    /**
     * 删除表
     */
    public static function dropIfExists(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`;";
        self::getConnection()->execute($sql);
    }

    private static function getConnection()
    {
        return app(DatabaseManager::class)->connection();
    }
}
