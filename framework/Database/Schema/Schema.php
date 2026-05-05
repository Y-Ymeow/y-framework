<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

use Framework\Database\Connection\Manager;
use Framework\Database\Schema\Grammars\MySqlGrammar;
use Framework\Database\Schema\Grammars\SqliteGrammar;

class Schema
{
    private Manager $manager;
    private Grammar $grammar;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    private function getGrammar(string $driver): Grammar
    {
        return match ($driver) {
            'sqlite' => new SqliteGrammar(),
            default => new MySqlGrammar(),
        };
    }

    public function create(string $table, callable $callback): void
    {
        $conn = $this->manager->connection();
        $driver = $conn->getDriverName();
        $blueprint = new Blueprint($table, $driver);
        $callback($blueprint);

        $grammar = $this->getGrammar($driver);
        $sql = $grammar->compileCreate($blueprint);

        $conn->execute($sql);
    }

    public function drop(string $table): void
    {
        $conn = $this->manager->connection();
        $driver = $conn->getDriverName();
        $blueprint = new Blueprint($table, $driver);

        $grammar = $this->getGrammar($driver);
        $sql = $grammar->compileDrop($blueprint);

        $conn->execute($sql);
    }

    public function dropIfExists(string $table): void
    {
        $this->drop($table);
    }

    public function table(string $table, callable $callback): void
    {
        $conn = $this->manager->connection();
        $driver = $conn->getDriverName();
        $blueprint = new Blueprint($table, $driver);
        $callback($blueprint);

        $grammar = $this->getGrammar($driver);
        $sqls = $grammar->compileAlter($blueprint);

        foreach ($sqls as $sql) {
            $conn->execute($sql);
        }
    }

    public function hasTable(string $table): bool
    {
        $conn = $this->manager->connection();
        $driver = $conn->getDriverName();

        if ($driver === 'sqlite') {
            $sql = "SELECT 1 FROM sqlite_master WHERE type='table' AND name=?";
        } else {
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
        }

        $result = $conn->queryOne($sql, [$table]);
        return $result !== null;
    }

    public function hasColumn(string $table, string $column): bool
    {
        $driver = $this->manager->connection()->getDriverName();
        $conn = $this->manager->connection();

        if ($driver === 'sqlite') {
            $sql = "PRAGMA table_info(\"{$table}\")";
            $rows = $conn->query($sql);
            foreach ($rows as $row) {
                if ($row['name'] === $column) return true;
            }
            return false;
        } else {
            $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
            $result = $conn->queryOne($sql, [$table, $column]);
            return $result !== null;
        }
    }

    public function rename(string $from, string $to): void
    {
        $driver = $this->manager->connection()->getDriverName();
        $conn = $this->manager->connection();

        if ($driver === 'sqlite') {
            $sql = "ALTER TABLE \"{$from}\" RENAME TO \"{$to}\"";
        } else {
            $sql = "RENAME TABLE `{$from}` TO `{$to}`";
        }

        $conn->execute($sql);
    }

    public function addColumn(string $table, string $column, string $type, array $options = []): void
    {
        $driver = $this->manager->connection()->getDriverName();
        $conn = $this->manager->connection();

        if ($driver === 'sqlite') {
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

        if (($options['after'] ?? null) && $driver !== 'sqlite') {
            $sql .= " AFTER `{$options['after']}`";
        }

        $conn->execute($sql);
    }

    public function dropColumn(string $table, string $column): void
    {
        $driver = $this->manager->connection()->getDriverName();
        $conn = $this->manager->connection();

        if ($driver === 'sqlite') {
            $sql = "ALTER TABLE \"{$table}\" DROP COLUMN \"{$column}\"";
        } else {
            $sql = "ALTER TABLE `{$table}` DROP COLUMN `{$column}`";
        }

        $conn->execute($sql);
    }
}