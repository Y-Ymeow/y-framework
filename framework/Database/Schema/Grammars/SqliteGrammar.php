<?php

declare(strict_types=1);

namespace Framework\Database\Schema\Grammars;

use Framework\Database\Schema\Blueprint;
use Framework\Database\Schema\Grammar;

class SqliteGrammar extends Grammar
{
    protected function getQuote(): string
    {
        return '"';
    }

    public function compileCreate(Blueprint $blueprint): string
    {
        $sql = "CREATE TABLE {$this->wrap($blueprint->getTable())} (\n";

        $parts = [];
        foreach ($blueprint->getColumns() as $name => $column) {
            $parts[] = '  ' . $this->compileColumn($name, $column);
        }

        if ($pk = $blueprint->getPrimaryKey()) {
            $pkColumn = $blueprint->getColumns()[$pk] ?? null;
            $isAutoInc = isset($pkColumn['auto_increment']) && $pkColumn['auto_increment'];
            if (!$isAutoInc) {
                $parts[] = "  PRIMARY KEY (\"{$pk}\")";
            }
        }

        foreach ($blueprint->getIndexes() as $index) {
            if ($index['type'] === 'unique') {
                $cols = implode('", "', $index['columns']);
                $parts[] = "  UNIQUE (\"{$cols}\")";
            }
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $parts[] = '  ' . $this->compileForeignKey($fk);
        }

        $sql .= implode(",\n", $parts);
        $sql .= "\n)";

        return $sql;
    }

    public function compileDrop(Blueprint $blueprint): string
    {
        return "DROP TABLE IF EXISTS {$this->wrap($blueprint->getTable())}";
    }

    public function compileAlter(Blueprint $blueprint): array
    {
        $sqls = [];
        $table = $blueprint->getTable();

        foreach ($blueprint->getColumns() as $column => $definition) {
            $colSql = $this->compileColumn($column, $definition);
            $sqls[] = "ALTER TABLE {$this->wrap($table)} ADD COLUMN {$colSql}";
        }

        // SQLite doesn't support creating indexes in ALTER TABLE
        // Foreign keys must be in CREATE TABLE

        return $sqls;
    }

    protected function compileColumn(string $name, array $column): string
    {
        $sql = "{$this->wrap($name)} ";
        $sql .= $this->compileColumnType($column);

        if (isset($column['auto_increment']) && $column['auto_increment']) {
            $sql .= ' PRIMARY KEY AUTOINCREMENT';
        } else {
            $sql .= $this->compileColumnNullable($column);
            $sql .= $this->compileColumnDefault($column);
        }

        return $sql;
    }

    protected function compileColumnType(array $column): string
    {
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
            'enum', 'set' => 'TEXT',
            default => strtoupper($column['type']),
        };
    }

    protected function compileIndex(array $index): string
    {
        $columns = implode('", "', $index['columns']);
        return $index['type'] === 'unique'
            ? "UNIQUE INDEX {$this->wrap($index['name'])} (\"{$columns}\")"
            : "INDEX {$this->wrap($index['name'])} (\"{$columns}\")";
    }

    protected function compileForeignKey(array $fk): string
    {
        return "FOREIGN KEY ({$this->wrap($fk['column'])}) REFERENCES {$this->wrap($fk['on'])} ({$this->wrap($fk['references'])})";
    }
}