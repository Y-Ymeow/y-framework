<?php

declare(strict_types=1);

namespace Framework\Database;

class QueryBuilder
{
    private Connection $connection;
    private string $table;
    private array $selects = ['*'];
    private array $wheres = [];
    private array $whereBindings = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $groupBy = [];
    private array $having = [];
    private array $havingBindings = [];

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function select(string ...$columns): self
    {
        $this->selects = SqlValidator::validateColumns($columns);
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        SqlValidator::validateColumn($column);
        SqlValidator::validateOperator($operator);

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];
        $this->whereBindings[] = $value;

        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values): self
    {
        SqlValidator::validateColumn($column);
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
        ];
        foreach ($values as $v) {
            $this->whereBindings[] = $v;
        }
        return $this;
    }

    public function whereNull(string $column): self
    {
        SqlValidator::validateColumn($column);
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
        ];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        SqlValidator::validateColumn($column);
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'AND',
        ];
        return $this;
    }

    public function whereLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'LIKE',
            'value' => $pattern,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $pattern;
        return $this;
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'min' => $min,
            'max' => $max,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $min;
        $this->whereBindings[] = $max;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        SqlValidator::validateTable($table);
        SqlValidator::validateColumn($first);
        SqlValidator::validateOperator($operator);
        SqlValidator::validateColumn($second);

        $joinType = strtoupper($type);
        $allowedTypes = ['INNER', 'LEFT', 'RIGHT', 'CROSS', 'FULL'];
        if (!in_array($joinType, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid join type: {$type}. Allowed types: " . implode(', ', $allowedTypes));
        }

        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $joinType,
        ];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        SqlValidator::validateColumn($column);
        $direction = SqlValidator::validateDirection($direction);
        $this->orderBy[] = SqlValidator::escapeIdentifier($column) . " {$direction}";
        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $validated = SqlValidator::validateColumns($columns);
        $this->groupBy = $validated;
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        SqlValidator::validateColumn($column);
        $operator = SqlValidator::validateOperator($operator);
        $this->having[] = SqlValidator::escapeIdentifier($column) . " {$operator} ?";
        $this->havingBindings[] = $value;
        return $this;
    }

    public function get(): array
    {
        return $this->connection->query($this->toSql(), $this->getBindings());
    }

    public function first(): ?array
    {
        return $this->connection->queryOne($this->limit(1)->toSql(), $this->getBindings());
    }

    public function find(mixed $id, string $column = 'id'): ?array
    {
        return $this->where($column, $id)->first();
    }

    public function count(string $column = '*'): int
    {
        if ($column !== '*') {
            SqlValidator::validateColumn($column);
        }
        $column = SqlValidator::escapeIdentifier($column);
        $originalSelects = $this->selects;
        $this->selects = ["COUNT({$column}) as _count"];
        $result = $this->connection->queryOne($this->toSql(), $this->getBindings());
        $this->selects = $originalSelects;
        return (int)($result['_count'] ?? 0);
    }

    public function sum(string $column): mixed
    {
        SqlValidator::validateColumn($column);
        $column = SqlValidator::escapeIdentifier($column);
        $originalSelects = $this->selects;
        $this->selects = ["SUM({$column}) as _sum"];
        $result = $this->connection->queryOne($this->toSql(), $this->getBindings());
        $this->selects = $originalSelects;
        return $result['_sum'] ?? 0;
    }

    public function avg(string $column): mixed
    {
        SqlValidator::validateColumn($column);
        $column = SqlValidator::escapeIdentifier($column);
        $originalSelects = $this->selects;
        $this->selects = ["AVG({$column}) as _avg"];
        $result = $this->connection->queryOne($this->toSql(), $this->getBindings());
        $this->selects = $originalSelects;
        return $result['_avg'] ?? 0;
    }

    public function max(string $column): mixed
    {
        SqlValidator::validateColumn($column);
        $column = SqlValidator::escapeIdentifier($column);
        $originalSelects = $this->selects;
        $this->selects = ["MAX({$column}) as _max"];
        $result = $this->connection->queryOne($this->toSql(), $this->getBindings());
        $this->selects = $originalSelects;
        return $result['_max'] ?? null;
    }

    public function min(string $column): mixed
    {
        SqlValidator::validateColumn($column);
        $column = SqlValidator::escapeIdentifier($column);
        $originalSelects = $this->selects;
        $this->selects = ["MIN({$column}) as _min"];
        $result = $this->connection->queryOne($this->toSql(), $this->getBindings());
        $this->selects = $originalSelects;
        return $result['_min'] ?? null;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = (clone $this)->count();
        $offset = ($page - 1) * $perPage;
        $items = $this->limit($perPage)->offset($offset)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    public function insert(array $data): int
    {
        return $this->connection->insert($this->table, $data);
    }

    public function update(array $data): int
    {
        [$whereSql, $whereBindings] = $this->buildWhere();
        if (empty($whereSql)) {
            throw new \RuntimeException('Update without WHERE clause is not allowed');
        }
        return $this->connection->update($this->table, $data, $whereSql, $whereBindings);
    }

    public function delete(): int
    {
        [$whereSql, $whereBindings] = $this->buildWhere();
        if (empty($whereSql)) {
            throw new \RuntimeException('Delete without WHERE clause is not allowed');
        }
        return $this->connection->delete($this->table, $whereSql, $whereBindings);
    }

    public function toSql(): string
    {
        $select = implode(', ', $this->selects);
        $table = SqlValidator::escapeIdentifier($this->table);
        $sql = "SELECT {$select} FROM {$table}";

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $joinTable = SqlValidator::escapeIdentifier($join['table']);
                $first = SqlValidator::escapeIdentifier($join['first']);
                $second = SqlValidator::escapeIdentifier($join['second']);
                $sql .= " {$join['type']} JOIN {$joinTable} ON {$first} {$join['operator']} {$second}";
            }
        }

        [$whereSql] = $this->buildWhere();
        if ($whereSql) $sql .= " WHERE {$whereSql}";

        if (!empty($this->groupBy)) {
            $groupColumns = array_map(fn($col) => SqlValidator::escapeIdentifier($col), $this->groupBy);
            $sql .= ' GROUP BY ' . implode(', ', $groupColumns);
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";

        return $sql;
    }

    public function getBindings(): array
    {
        return array_merge($this->whereBindings, $this->havingBindings);
    }

    private function buildWhere(): array
    {
        if (empty($this->wheres)) return ['', []];

        $clauses = [];
        $bindings = [];

        foreach ($this->wheres as $i => $where) {
            $clause = '';
            if ($i > 0) $clause = $where['boolean'] . ' ';
            $column = SqlValidator::escapeIdentifier($where['column']);

            switch ($where['type']) {
                case 'basic':
                    $clause .= "{$column} {$where['operator']} ?";
                    $bindings[] = $where['value'];
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clause .= "{$column} IN ({$placeholders})";
                    foreach ($where['values'] as $value) {
                        $bindings[] = $value;
                    }
                    break;
                case 'null':
                    $clause .= "{$column} IS NULL";
                    break;
                case 'not_null':
                    $clause .= "{$column} IS NOT NULL";
                    break;
                case 'between':
                    $clause .= "{$column} BETWEEN ? AND ?";
                    $bindings[] = $where['min'];
                    $bindings[] = $where['max'];
                    break;
            }

            $clauses[] = $clause;
        }

        return [implode(' ', $clauses), $bindings];
    }

    private function __clone() {}
}
