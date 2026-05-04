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
        
        // Generate ALTER TABLE statements for column/index/foreign key changes
        $sqls = $this->compileAlterSql($blueprint);
        foreach ($sqls as $sql) {
            $this->connection->execute($sql);
        }
    }

    /**
     * Compile ALTER TABLE statements from Blueprint
     */
    private function compileAlterSql(Blueprint $blueprint): array
    {
        $driver = $blueprint->getDriver();
        $q = $driver === 'sqlite' ? '"' : '`';
        $table = $blueprint->getTable();
        $sqls = [];

        // Add columns
        foreach ($blueprint->getColumns() as $column => $definition) {
            $colSql = $this->compileColumnDefinition($column, $definition, $driver, $q);
            $sqls[] = "ALTER TABLE {$q}{$table}{$q} ADD COLUMN {$colSql}";
        }

        // Add indexes
        foreach ($blueprint->getIndexes() as $index) {
            $cols = implode($q . ', ' . $q, $index['columns']);
            if ($index['type'] === 'unique') {
                $sqls[] = "CREATE UNIQUE INDEX {$q}{$index['name']}{$q} ON {$q}{$table}{$q} ({$q}{$cols}{$q})";
            } else {
                $sqls[] = "CREATE INDEX {$q}{$index['name']}{$q} ON {$q}{$table}{$q} ({$q}{$cols}{$q})";
            }
        }

        // Add foreign keys (SQLite requires them in CREATE TABLE, but we can try ALTER)
        foreach ($blueprint->getForeignKeys() as $fk) {
            if ($driver === 'sqlite') {
                // SQLite: foreign keys must be in CREATE TABLE, skip for ALTER
                // Framework will handle this in migration design
                continue;
            }
            $sqls[] = "ALTER TABLE {$q}{$table}{$q} ADD CONSTRAINT {$q}fk_{$table}_{$fk['column']}{$q} " .
                "FOREIGN KEY ({$q}{$fk['column']}{$q}) REFERENCES {$q}{$fk['on']}{$q} ({$q}{$fk['references']}{$q})";
        }

        return $sqls;
    }

    /**
     * Compile column definition for ALTER TABLE
     */
    private function compileColumnDefinition(string $column, array $def, string $driver, string $q): string
    {
        $type = match ($def['type']) {
            'varchar' => "VARCHAR({$def['length']})",
            'char' => "CHAR({$def['length']})",
            'text', 'longtext', 'mediumtext' => 'TEXT',
            'int', 'tinyint', 'bigint', 'smallint', 'mediumint' => 'INTEGER',
            'decimal' => "DECIMAL({$def['precision']}, {$def['scale']})",
            'float' => "FLOAT({$def['precision']}, {$def['scale']})",
            'double' => "DOUBLE({$def['precision']}, {$def['scale']})",
            'date', 'time', 'year' => 'TEXT',
            'datetime', 'timestamp' => 'TEXT',
            'json', 'blob' => 'BLOB',
            'enum', 'set' => "TEXT",
            default => strtoupper($def['type']),
        };

        $sql = "{$q}{$column}{$q} {$type}";

        if (isset($def['auto_increment']) && $def['auto_increment']) {
            // Auto-increment columns can't be added via ALTER TABLE in SQLite
            // Skip for ALTER, should be handled in CREATE TABLE
            if ($driver !== 'sqlite') {
                $sql .= ' AUTO_INCREMENT';
            }
        }

        if (!($def['nullable'] ?? true)) {
            $sql .= ' NOT NULL';
        }

        if (isset($def['default'])) {
            $default = $def['default'];
            if ($default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($default)) {
                $sql .= ' DEFAULT ' . ($default ? 1 : 0);
            } elseif (is_int($default) || is_float($default)) {
                $sql .= " DEFAULT {$default}";
            } elseif (strtoupper((string)$default) === 'CURRENT_TIMESTAMP') {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                $sql .= " DEFAULT '{$default}'";
            }
        }

        return $sql;
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
