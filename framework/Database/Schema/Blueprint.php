<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

class Blueprint
{
    private string $table;
    private string $driver = 'mysql';
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private ?string $primaryKey = null;

    public function __construct(string $table, string $driver = 'mysql')
    {
        $this->table = $table;
        $this->driver = $driver;
    }

    public function id(string $column = 'id'): self
    {
        $this->columns[$column] = [
            'type' => 'bigint',
            'unsigned' => true,
            'auto_increment' => true,
            'nullable' => false,
        ];
        $this->primaryKey = $column;
        return $this;
    }

    public function bigIncrements(string $column = 'id'): self
    {
        return $this->id($column);
    }

    public function increments(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'int',
            'unsigned' => true,
            'auto_increment' => true,
            'nullable' => false,
        ];
        $this->primaryKey = $column;
        return $this;
    }

    public function uuid(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'char',
            'length' => 36,
            'nullable' => false,
        ];
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[$column] = [
            'type' => 'varchar',
            'length' => $length,
            'nullable' => false,
        ];
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'text',
            'nullable' => true,
        ];
        return $this;
    }

    public function mediumText(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'mediumtext',
            'nullable' => true,
        ];
        return $this;
    }

    public function longText(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'longtext',
            'nullable' => true,
        ];
        return $this;
    }

    public function binary(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'blob',
            'nullable' => true,
        ];
        return $this;
    }

    public function integer(string $column, bool $unsigned = false): self
    {
        $this->columns[$column] = [
            'type' => 'int',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    public function unsignedInteger(string $column): self
    {
        return $this->integer($column, true);
    }

    public function bigInteger(string $column, bool $unsigned = false): self
    {
        $this->columns[$column] = [
            'type' => 'bigint',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    public function unsignedBigInteger(string $column): self
    {
        return $this->bigInteger($column, true);
    }

    public function tinyInteger(string $column, bool $unsigned = false): self
    {
        $this->columns[$column] = [
            'type' => 'tinyint',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    public function unsignedTinyInteger(string $column): self
    {
        return $this->tinyInteger($column, true);
    }

    public function smallInteger(string $column, bool $unsigned = false): self
    {
        $this->columns[$column] = [
            'type' => 'smallint',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    public function unsignedSmallInteger(string $column): self
    {
        return $this->smallInteger($column, true);
    }

    public function mediumInteger(string $column, bool $unsigned = false): self
    {
        $this->columns[$column] = [
            'type' => 'mediumint',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    public function unsignedMediumInteger(string $column): self
    {
        return $this->mediumInteger($column, true);
    }

    public function boolean(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'tinyint',
            'length' => 1,
            'nullable' => false,
            'default' => 0,
        ];
        return $this;
    }

    public function decimal(string $column, int $precision = 10, int $scale = 2): self
    {
        $this->columns[$column] = [
            'type' => 'decimal',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
        ];
        return $this;
    }

    public function float(string $column, int $precision = 10, int $scale = 2): self
    {
        $this->columns[$column] = [
            'type' => 'float',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
        ];
        return $this;
    }

    public function double(string $column, int $precision = 15, int $scale = 8): self
    {
        $this->columns[$column] = [
            'type' => 'double',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
        ];
        return $this;
    }

    public function char(string $column, int $length = 255): self
    {
        $this->columns[$column] = [
            'type' => 'char',
            'length' => $length,
            'nullable' => false,
        ];
        return $this;
    }

    public function time(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'time',
            'nullable' => true,
        ];
        return $this;
    }

    public function year(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'year',
            'nullable' => true,
        ];
        return $this;
    }

    public function date(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'date',
            'nullable' => true,
        ];
        return $this;
    }

    public function datetime(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'datetime',
            'nullable' => true,
        ];
        return $this;
    }

    public function timestamp(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'timestamp',
            'nullable' => true,
        ];
        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
        return $this;
    }

    public function softDeletes(string $column = 'deleted_at'): self
    {
        $this->timestamp($column)->nullable();
        return $this;
    }

    public function json(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'json',
            'nullable' => true,
        ];
        return $this;
    }

    public function enum(string $column, array $values): self
    {
        $this->columns[$column] = [
            'type' => 'enum',
            'values' => $values,
            'nullable' => false,
        ];
        return $this;
    }

    public function set(string $column, array $values): self
    {
        $this->columns[$column] = [
            'type' => 'set',
            'values' => $values,
            'nullable' => true,
        ];
        return $this;
    }

    public function nullable(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['nullable'] = true;
        }
        return $this;
    }

    public function default(mixed $value): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['default'] = $value;
        }
        return $this;
    }

    public function unsigned(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['unsigned'] = true;
        }
        return $this;
    }

    public function comment(string $value): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['comment'] = $value;
        }
        return $this;
    }

    public function after(string $column): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['after'] = $column;
        }
        return $this;
    }

    public function useCurrent(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['default'] = 'CURRENT_TIMESTAMP';
        }
        return $this;
    }

    public function unique(string|array $columns = '', ?string $name = null): self
    {
        if (is_string($columns) && $columns === '') {
            $columns = [array_key_last($this->columns)];
        } elseif (is_string($columns)) {
            $columns = [$columns];
        }
        $name ??= "idx_{$this->table}_" . implode('_', $columns) . '_unique';
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => $columns,
            'name' => $name,
        ];
        return $this;
    }

    public function index(string|array $columns = '', ?string $name = null): self
    {
        if (is_string($columns) && $columns === '') {
            $columns = [array_key_last($this->columns)];
        } elseif (is_string($columns)) {
            $columns = [$columns];
        }
        $name ??= "idx_{$this->table}_" . implode('_', $columns);
        $this->indexes[] = [
            'type' => 'index',
            'columns' => $columns,
            'name' => $name,
        ];
        return $this;
    }

    public function foreign(string $column): ForeignKeyBuilder
    {
        return new ForeignKeyBuilder($this, $column);
    }

    public function foreignId(string $column): ForeignIdColumnDefinition
    {
        $this->unsignedBigInteger($column);
        return new ForeignIdColumnDefinition($this, $column);
    }

    public function morphs(string $name): self
    {
        $this->string("{$name}_type");
        $this->unsignedBigInteger("{$name}_id");
        $this->index(["{$name}_type", "{$name}_id"]);
        return $this;
    }

    public function addForeignKey(string $column, string $references, string $on, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): self
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => $references,
            'on' => $on,
            'on_delete' => $onDelete,
            'on_update' => $onUpdate,
        ];
        return $this;
    }

    public function rememberToken(): self
    {
        $this->string('remember_token', 100)->nullable();
        return $this;
    }

    public function primary(array|string $columns = []): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->primaryKey = $columns[0] ?? null;
        return $this;
    }

    public function dropColumn(string|array $columns): self
    {
        foreach ((array) $columns as $col) {
            unset($this->columns[$col]);
        }
        return $this;
    }

    public function dropIndex(string|array $columns): self
    {
        $names = is_array($columns) ? $columns : [$columns];
        $this->indexes = array_filter($this->indexes, fn ($idx) => !in_array($idx['name'], $names));
        return $this;
    }

    public function dropUnique(string|array $columns): self
    {
        $names = is_array($columns) ? $columns : [$columns];
        $this->indexes = array_filter($this->indexes, fn ($idx) => !in_array($idx['name'], $names));
        return $this;
    }

    public function dropForeign(string|array $columns): self
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $this->foreignKeys = array_filter($this->foreignKeys, fn ($fk) => !in_array($fk['column'], $cols));
        return $this;
    }

    public function toSql(): string
    {
        $q = $this->driver === 'sqlite' ? '"' : '`';

        $sql = "CREATE TABLE {$q}{$this->table}{$q} (\n";
        $parts = [];

        foreach ($this->columns as $name => $column) {
            $parts[] = '  ' . $this->compileColumn($name, $column);
        }

        if ($this->primaryKey) {
            $pkColumn = $this->columns[$this->primaryKey] ?? null;
            $isAutoInc = isset($pkColumn['auto_increment']) && $pkColumn['auto_increment'];

            if ($this->driver === 'sqlite' && $isAutoInc) {
            } elseif ($this->driver === 'sqlite') {
                $parts[] = "  PRIMARY KEY (\"{$this->primaryKey}\")";
            } else {
                $parts[] = "  PRIMARY KEY (`{$this->primaryKey}`)";
            }
        }

        if ($this->driver === 'sqlite') {
            foreach ($this->indexes as $index) {
                if ($index['type'] === 'unique') {
                    $cols = implode('", "', $index['columns']);
                    $parts[] = "  UNIQUE (\"{$cols}\")";
                }
            }

            foreach ($this->foreignKeys as $fk) {
                $parts[] = '  ' . $this->compileForeignKey($fk);
            }

            $sql .= implode(",\n", $parts);
            $sql .= "\n)";
        } else {
            foreach ($this->indexes as $index) {
                $parts[] = '  ' . $this->compileIndex($index);
            }

            foreach ($this->foreignKeys as $fk) {
                $parts[] = '  ' . $this->compileForeignKey($fk);
            }

            $sql .= implode(",\n", $parts);
            $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        return $sql;
    }

    public function toDropSql(): string
    {
        $q = $this->driver === 'sqlite' ? '"' : '`';
        return "DROP TABLE IF EXISTS {$q}{$this->table}{$q}";
    }

    private function compileColumn(string $name, array $column): string
    {
        $q = $this->driver === 'sqlite' ? '"' : '`';
        $sql = "{$q}{$name}{$q} ";

        if ($this->driver === 'sqlite') {
            $sql .= match ($column['type']) {
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
            $sql .= match ($column['type']) {
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

        if (isset($column['auto_increment']) && $column['auto_increment']) {
            if ($this->driver === 'sqlite') {
                $sql .= ' PRIMARY KEY AUTOINCREMENT';
            } else {
                $sql .= ' AUTO_INCREMENT';
            }
        } elseif (!($column['nullable'] ?? true)) {
            $sql .= ' NOT NULL';
        } else {
            $sql .= ' NULL';
        }

        if (isset($column['default'])) {
            $default = $column['default'];
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

        if (isset($column['comment']) && $this->driver !== 'sqlite') {
            $sql .= " COMMENT '" . addslashes($column['comment']) . "'";
        }

        return $sql;
    }

    private function compileIndex(array $index): string
    {
        $columns = implode('`, `', $index['columns']);
        return match ($index['type']) {
            'unique' => "UNIQUE KEY `{$index['name']}` (`{$columns}`)",
            'index' => "KEY `{$index['name']}` (`{$columns}`)",
            default => "KEY `{$index['name']}` (`{$columns}`)",
        };
    }

    private function compileForeignKey(array $fk): string
    {
        $q = $this->driver === 'sqlite' ? '"' : '`';
        if ($this->driver === 'sqlite') {
            return "FOREIGN KEY ({$q}{$fk['column']}{$q}) REFERENCES {$q}{$fk['on']}{$q} ({$q}{$fk['references']}{$q})";
        }
        return "CONSTRAINT `fk_{$this->table}_{$fk['column']}` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['on']}` (`{$fk['references']}`) ON DELETE {$fk['on_delete']} ON UPDATE {$fk['on_update']}";
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }
}
