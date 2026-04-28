<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

use Framework\Database\Connection;

class Schema
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, $this->connection->getDriverName());
        $callback($blueprint);
        $sql = $blueprint->toSql();
        $this->connection->execute($sql);
    }

    public function drop(string $table): void
    {
        $blueprint = new Blueprint($table, $this->connection->getDriverName());
        $this->connection->execute($blueprint->toDropSql());
    }

    public function dropIfExists(string $table): void
    {
        $this->drop($table);
    }

    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, $this->connection->getDriverName());
        $callback($blueprint);
    }

    public function hasTable(string $table): bool
    {
        if ($this->connection->getDriverName() === 'sqlite') {
            $sql = "SELECT 1 FROM sqlite_master WHERE type='table' AND name=?";
        } else {
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
        }
        $result = $this->connection->queryOne($sql, [$table]);
        return $result !== null;
    }

    public function hasColumn(string $table, string $column): bool
    {
        if ($this->connection->getDriverName() === 'sqlite') {
            $sql = "PRAGMA table_info(\"{$table}\")";
            $rows = $this->connection->query($sql);
            foreach ($rows as $row) {
                if ($row['name'] === $column) return true;
            }
            return false;
        } else {
            $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
            $result = $this->connection->queryOne($sql, [$table, $column]);
            return $result !== null;
        }
    }

    public function rename(string $from, string $to): void
    {
        if ($this->connection->getDriverName() === 'sqlite') {
            $sql = "ALTER TABLE \"{$from}\" RENAME TO \"{$to}\"";
        } else {
            $sql = "RENAME TABLE `{$from}` TO `{$to}`";
        }
        $this->connection->execute($sql);
    }

    public function addColumn(string $table, string $column, string $type, array $options = []): void
    {
        if ($this->connection->getDriverName() === 'sqlite') {
            $sql = "ALTER TABLE \"{$table}\" ADD COLUMN \"{$column}\" {$type}";
        } else {
            $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$type}";
        }

        if (!($options['nullable'] ?? true)) {
            $sql .= ' NOT NULL';
        }

        if (isset($options['default'])) {
            $sql .= " DEFAULT '{$options['default']}'";
        }

        if (($options['after'] ?? null) && $this->connection->getDriverName() !== 'sqlite') {
            $sql .= " AFTER `{$options['after']}`";
        }

        $this->connection->execute($sql);
    }

    public function dropColumn(string $table, string $column): void
    {
        if ($this->connection->getDriverName() === 'sqlite') {
            $sql = "ALTER TABLE \"{$table}\" DROP COLUMN \"{$column}\"";
        } else {
            $sql = "ALTER TABLE `{$table}` DROP COLUMN `{$column}`";
        }
        $this->connection->execute($sql);
    }
}
