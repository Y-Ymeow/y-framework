<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

/**
 * 表结构蓝图，用于生成 DDL SQL
 */
final class Blueprint
{
    /** @var array<int, array{type: string, name: string, options: array}> */
    private array $columns = [];
    
    /** @var array<int, array{type: string, columns: array, name: ?string}> */
    private array $indexes = [];

    public function __construct(
        private readonly string $table
    ) {
    }

    public function id(string $name = 'id'): self
    {
        $this->columns[] = ['type' => 'id', 'name' => $name, 'options' => []];
        return $this;
    }

    public function string(string $name, int $length = 255): self
    {
        $this->columns[] = ['type' => 'string', 'name' => $name, 'options' => ['length' => $length]];
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[] = ['type' => 'text', 'name' => $name, 'options' => []];
        return $this;
    }

    public function integer(string $name): self
    {
        $this->columns[] = ['type' => 'integer', 'name' => $name, 'options' => []];
        return $this;
    }

    public function boolean(string $name): self
    {
        $this->columns[] = ['type' => 'boolean', 'name' => $name, 'options' => []];
        return $this;
    }

    public function timestamp(string $name): self
    {
        $this->columns[] = ['type' => 'timestamp', 'name' => $name, 'options' => []];
        return $this;
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
    }

    public function unique(string|array $columns, ?string $name = null): self
    {
        $this->indexes[] = ['type' => 'unique', 'columns' => (array)$columns, 'name' => $name];
        return $this;
    }

    public function toSql(): string
    {
        $statements = [];
        $colStrings = [];

        foreach ($this->columns as $col) {
            $colStrings[] = $this->buildColumnSql($col);
        }

        $sql = "CREATE TABLE `{$this->table}` (\n  " . implode(",\n  ", $colStrings);
        
        // 处理索引（简单实现）
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'unique') {
                $cols = implode('`, `', $index['columns']);
                $sql .= ",\n  UNIQUE (`{$cols}`)";
            }
        }

        $sql .= "\n);";
        return $sql;
    }

    private function buildColumnSql(array $col): string
    {
        $sql = "`{$col['name']}` ";
        
        switch ($col['type']) {
            case 'id':
                $sql .= "INTEGER PRIMARY KEY AUTOINCREMENT";
                break;
            case 'string':
                $sql .= "VARCHAR({$col['options']['length']}) NOT NULL";
                break;
            case 'text':
                $sql .= "TEXT NOT NULL";
                break;
            case 'integer':
                $sql .= "INTEGER NOT NULL";
                break;
            case 'boolean':
                $sql .= "BOOLEAN NOT NULL DEFAULT 0";
                break;
            case 'timestamp':
                $sql .= "DATETIME DEFAULT CURRENT_TIMESTAMP";
                break;
        }

        return $sql;
    }
}
