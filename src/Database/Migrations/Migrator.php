<?php

declare(strict_types=1);

namespace Framework\Database\Migrations;

use Framework\Database\Connection;
use Framework\Database\Schema\Schema;
use Framework\Database\Schema\Blueprint;

final class Migrator
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $migrationsPath
    ) {
    }

    /**
     * 执行所有未运行的迁移
     */
    public function migrate(): array
    {
        $this->ensureMigrationTableExists();
        $ran = $this->getRanMigrations();
        $files = glob($this->migrationsPath . '/*.php');
        $executed = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $ran)) {
                $this->runMigration($file, $name);
                $executed[] = $name;
            }
        }

        return $executed;
    }

    private function ensureMigrationTableExists(): void
    {
        // 这种简单的 DDL 可以直接写
        $this->connection->execute("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )
        ");
    }

    private function getRanMigrations(): array
    {
        $rows = $this->connection->select("SELECT migration FROM migrations");
        return array_column($rows, 'migration');
    }

    private function runMigration(string $file, string $name): void
    {
        $migration = require $file;
        
        $this->connection->transaction(function() use ($migration, $name) {
            $migration->up();
            $this->connection->execute(
                "INSERT INTO migrations (migration, batch) VALUES (?, ?)", 
                [$name, 1]
            );
        });
    }

    /**
     * 回滚最后一批迁移 (简化版)
     */
    public function rollback(): array
    {
        // 逻辑类似，调用 $migration->down()
        return [];
    }
}
