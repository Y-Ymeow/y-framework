<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

abstract class Grammar
{
    abstract public function compileCreate(Blueprint $blueprint): string;

    abstract public function compileDrop(Blueprint $blueprint): string;

    abstract public function compileAlter(Blueprint $blueprint): array;

    abstract protected function compileColumn(string $name, array $column): string;

    abstract protected function compileIndex(array $index): string;

    abstract protected function compileForeignKey(array $fk): string;

    abstract protected function getQuote(): string;

    protected function wrap(string $identifier): string
    {
        return $this->getQuote() . $identifier . $this->getQuote();
    }

    protected function compileColumnType(array $column): string
    {
        return match ($column['type']) {
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
    }

    protected function compileColumnNullable(array $column): string
    {
        if (isset($column['auto_increment'])) {
            return '';
        }

        return ($column['nullable'] ?? true) ? ' NULL' : ' NOT NULL';
    }

    protected function compileColumnDefault(array $column): string
    {
        if (!isset($column['default'])) {
            return '';
        }

        $default = $column['default'];
        if ($default === null) {
            return ' DEFAULT NULL';
        }
        if (is_bool($default)) {
            return ' DEFAULT ' . ($default ? 1 : 0);
        }
        if (is_int($default) || is_float($default)) {
            return " DEFAULT {$default}";
        }
        if (strtoupper((string)$default) === 'CURRENT_TIMESTAMP') {
            return ' DEFAULT CURRENT_TIMESTAMP';
        }

        return " DEFAULT '{$default}'";
    }

    protected function compileColumnAutoIncrement(array $column): string
    {
        if (!isset($column['auto_increment'])) {
            return '';
        }

        return ' AUTO_INCREMENT';
    }

    protected function compileColumnComment(array $column): string
    {
        if (!isset($column['comment'])) {
            return '';
        }

        return " COMMENT '" . addslashes($column['comment']) . "'";
    }
}