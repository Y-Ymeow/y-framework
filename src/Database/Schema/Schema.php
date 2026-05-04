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

        foreach ($blueprint->getColumns() as $name => $column) {
            if ($column['change'] ?? false) {
                $this->modifyColumn($table, $name, $this->compileColumnType($column), $column);
            } elseif (!$this->hasColumn($table, $name)) {
                $this->addColumn($table, $name, $this->compileColumnType($column), $column);
            }
        }
    }

    public function modifyColumn(string $table, string $column, string $type, array $options = []): void
    {
        if ($this->connection->getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN.
            return;
        }

        $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$type}";

        if (!($options['nullable'] ?? true)) {
            $sql .= ' NOT NULL';
        }

        if (isset($options['default'])) {
            $default = $options['default'];
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

        if (($options['after'] ?? null)) {
            $sql .= " AFTER `{$options['after']}`";
        }

        $this->connection->execute($sql);
    }

    private function compileColumnType(array $column): string
    {
        $driver = $this->connection->getDriverName();
        if ($driver === 'sqlite') {
            return match ($column['type']) {
                'varchar' => "VARCHAR({$column['length']})",
                'char' => "CHAR({$column['length']})",
                'text', 'longtext', 'mediumtext' => 'TEXT',
                'int', 'tinyint', 'bigint', 'smallint', 'mediumint' => 'INTEGER',
                'decimal' => "DECIMAL({$column['precision']}, {$column['scale']})",
                'float' => "FLOAT({$column['precision']}, {$column['scale']})",
                'double' => "DOUBLE({$column['precision']}, {$column['scale']})",
                'date', 'time', 'year' => 'TEXT',
                'datetime', 'timestamp' => 'TEXT',
                'json', 'blob' => 'BLOB',
                'enum', 'set' => "TEXT",
                default => strtoupper($column['type']),
            };
        } else {
            $sql = match ($column['type']) {
                'varchar' => "VARCHAR({$column['length']})",
                'char' => "CHAR({$column['length']})",
                'text' => 'TEXT',
                'mediumtext' => 'MEDIUMTEXT',
                'longtext' => 'LONGTEXT',
                'int' => ($column['unsigned'] ?? false) ? 'INT UNSIGNED' : 'INT',
                'tinyint' => ($column['unsigned'] ?? false) ? 'TINYINT UNSIGNED' : 'TINYINT',
                'smallint' => ($column['unsigned'] ?? false) ? 'SMALLINT UNSIGNED' : 'SMALLINT',
                'mediumint' => ($column['unsigned'] ?? false) ? 'MEDIUMINT UNSIGNED' : 'MEDIUMINT',
                'bigint' => ($column['unsigned'] ?? false) ? 'BIGINT UNSIGNED' : 'BIGINT',
                'decimal' => "DECIMAL({$column['precision']}, {$column['scale']})",
                'float' => "FLOAT({$column['precision']}, {$column['scale']})",
                'double' => "DOUBLE({$column['precision']}, {$column['scale']})",
                'date' => 'DATE',
                'time' => 'TIME',
                'year' => 'YEAR',
                'datetime' => 'DATETIME',
                'timestamp' => 'TIMESTAMP',
                'json' => 'JSON',
                'blob' => 'BLOB',
                'enum' => "ENUM('" . implode("', '", $column['values']) . "')",
                'set' => "SET('" . implode("', '", $column['values']) . "')",
                default => strtoupper($column['type']),
            };
            return $sql;
        }
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
