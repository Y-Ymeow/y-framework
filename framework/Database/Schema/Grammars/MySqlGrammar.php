<?php

declare(strict_types=1);

namespace Framework\Database\Schema\Grammars;

use Framework\Database\Schema\Blueprint;
use Framework\Database\Schema\Grammar;

class MySqlGrammar extends Grammar
{
    protected function getQuote(): string
    {
        return '`';
    }

    public function compileCreate(Blueprint $blueprint): string
    {
        $sql = "CREATE TABLE {$this->wrap($blueprint->getTable())} (\n";

        $parts = [];
        foreach ($blueprint->getColumns() as $name => $column) {
            $parts[] = '  ' . $this->compileColumn($name, $column);
        }

        if ($blueprint->getPrimaryKey()) {
            $pk = $blueprint->getPrimaryKey();
            $parts[] = "  PRIMARY KEY (`{$pk}`)";
        }

        foreach ($blueprint->getIndexes() as $index) {
            $parts[] = '  ' . $this->compileIndex($index);
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $parts[] = '  ' . $this->compileForeignKey($fk);
        }

        $sql .= implode(",\n", $parts);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

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

        foreach ($blueprint->getIndexes() as $index) {
            $cols = implode('`, `', $index['columns']);
            if ($index['type'] === 'unique') {
                $sqls[] = "CREATE UNIQUE INDEX {$this->wrap($index['name'])} ON {$this->wrap($table)} (`{$cols}`)";
            } else {
                $sqls[] = "CREATE INDEX {$this->wrap($index['name'])} ON {$this->wrap($table)} (`{$cols}`)";
            }
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $sqls[] = "ALTER TABLE {$this->wrap($table)} ADD CONSTRAINT {$this->wrap('fk_' . $table . '_' . $fk['column'])} " .
                "FOREIGN KEY ({$this->wrap($fk['column'])}) REFERENCES {$this->wrap($fk['on'])} ({$this->wrap($fk['references'])})";
        }

        return $sqls;
    }

    protected function compileColumn(string $name, array $column): string
    {
        $sql = "{$this->wrap($name)} ";
        $sql .= $this->compileColumnType($column);
        $sql .= $this->compileColumnAutoIncrement($column);
        $sql .= $this->compileColumnNullable($column);
        $sql .= $this->compileColumnDefault($column);
        $sql .= $this->compileColumnComment($column);

        return $sql;
    }

    protected function compileIndex(array $index): string
    {
        $columns = implode('`, `', $index['columns']);
        return match ($index['type']) {
            'unique' => "UNIQUE KEY {$this->wrap($index['name'])} (`{$columns}`)",
            'index' => "KEY {$this->wrap($index['name'])} (`{$columns}`)",
            default => "KEY {$this->wrap($index['name'])} (`{$columns}`)",
        };
    }

    protected function compileForeignKey(array $fk): string
    {
        return "CONSTRAINT {$this->wrap('fk_' . $this->table . '_' . $fk['column'])} " .
            "FOREIGN KEY ({$this->wrap($fk['column'])}) REFERENCES {$this->wrap($fk['on'])} ({$this->wrap($fk['references'])}) " .
            "ON DELETE {$fk['on_delete']} ON UPDATE {$fk['on_update']}";
    }

    private string $table = '';
}