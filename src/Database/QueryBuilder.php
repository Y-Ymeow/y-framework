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
    private bool $distinct = false;
    private ?string $lock = null;
    private string $softDeleteMode = 'exclude';
    private string $softDeleteColumn = 'deleted_at';

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

    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->having[] = $sql;
        $this->havingBindings = array_merge($this->havingBindings, $bindings);
        return $this;
    }

    public function whereNotBetween(string $column, mixed $min, mixed $max): self
    {
        SqlValidator::validateColumn($column);
        $this->wheres[] = [
            'type' => 'not_between',
            'column' => $column,
            'min' => $min,
            'max' => $max,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $min;
        $this->whereBindings[] = $max;
        return $this;
    }

    public function whereDate(string $column, string $operator, mixed $value): self
    {
        SqlValidator::validateColumn($column);
        SqlValidator::validateOperator($operator);
        $this->wheres[] = [
            'type' => 'date',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $value;
        return $this;
    }

    public function whereDay(string $column, string $operator, mixed $value): self
    {
        SqlValidator::validateColumn($column);
        SqlValidator::validateOperator($operator);
        $this->wheres[] = [
            'type' => 'date_part',
            'column' => $column,
            'part' => 'DAY',
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $value;
        return $this;
    }

    public function whereMonth(string $column, string $operator, mixed $value): self
    {
        SqlValidator::validateColumn($column);
        SqlValidator::validateOperator($operator);
        $this->wheres[] = [
            'type' => 'date_part',
            'column' => $column,
            'part' => 'MONTH',
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $value;
        return $this;
    }

    public function whereYear(string $column, string $operator, mixed $value): self
    {
        SqlValidator::validateColumn($column);
        SqlValidator::validateOperator($operator);
        $this->wheres[] = [
            'type' => 'date_part',
            'column' => $column,
            'part' => 'YEAR',
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];
        $this->whereBindings[] = $value;
        return $this;
    }

    public function whereColumn(string $column1, string $operator, string $column2): self
    {
        SqlValidator::validateColumn($column1);
        SqlValidator::validateOperator($operator);
        SqlValidator::validateColumn($column2);
        $this->wheres[] = [
            'type' => 'column',
            'column' => $column1,
            'operator' => $operator,
            'value' => $column2,
            'boolean' => 'AND',
        ];
        return $this;
    }

    public function whereExists(self $query): self
    {
        $this->wheres[] = [
            'type' => 'exists',
            'query' => $query,
            'boolean' => 'AND',
            'not' => false,
        ];
        $this->whereBindings = array_merge($this->whereBindings, $query->getBindings());
        return $this;
    }

    public function whereNotExists(self $query): self
    {
        $query->whereExists($query);
        $this->wheres[count($this->wheres) - 1]['not'] = true;
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function groupByRaw(string $sql, array $bindings = []): self
    {
        $this->groupBy[] = ['raw' => true, 'sql' => $sql];
        $this->whereBindings = array_merge($this->whereBindings, $bindings);
        return $this;
    }

    public function orderByRaw(string $sql, array $bindings = []): self
    {
        $this->orderBy[] = ['raw' => true, 'sql' => $sql];
        $this->whereBindings = array_merge($this->whereBindings, $bindings);
        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function lock(?string $lock = 'FOR UPDATE'): self
    {
        $this->lock = $lock;
        return $this;
    }

    public function sharedLock(): self
    {
        return $this->lock('LOCK IN SHARE MODE');
    }

    public function lockForUpdate(): self
    {
        return $this->lock('FOR UPDATE');
    }

    public function increment(string $column, int|float $amount = 1): int
    {
        SqlValidator::validateColumn($column);
        $escaped = SqlValidator::escapeIdentifier($column);
        [$whereSql, $whereBindings] = $this->buildWhere();
        if (empty($whereSql)) {
            throw new \RuntimeException('Increment without WHERE clause is not allowed');
        }
        $sql = "UPDATE " . SqlValidator::escapeIdentifier($this->table)
             . " SET {$escaped} = {$escaped} + ? WHERE {$whereSql}";
        $bindings = array_merge([$amount], $whereBindings);
        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    public function decrement(string $column, int|float $amount = 1): int
    {
        return $this->increment($column, -$amount);
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
        $distinctKeyword = $this->distinct ? 'DISTINCT ' : '';
        $select = implode(', ', $this->selects);
        $table = SqlValidator::escapeIdentifier($this->table);
        $sql = "SELECT {$distinctKeyword}{$select} FROM {$table}";

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
            $groupParts = [];
            foreach ($this->groupBy as $group) {
                if ($group['raw'] ?? false) {
                    $groupParts[] = $group['sql'];
                } else {
                    $groupParts[] = SqlValidator::escapeIdentifier($group);
                }
            }
            $sql .= ' GROUP BY ' . implode(', ', $groupParts);
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $order) {
                if ($order['raw'] ?? false) {
                    $orderParts[] = $order['sql'];
                } else {
                    $orderParts[] = $order;
                }
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        if ($this->limit !== null) $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        if ($this->lock !== null) $sql .= " {$this->lock}";

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
                case 'not_between':
                    $clause .= "{$column} NOT BETWEEN ? AND ?";
                    $bindings[] = $where['min'];
                    $bindings[] = $where['max'];
                    break;
                case 'date':
                    $clause .= "DATE({$column}) {$where['operator']} ?";
                    $bindings[] = $where['value'];
                    break;
                case 'date_part':
                    $part = $where['part'];
                    $clause .= "{$part}({$column}) {$where['operator']} ?";
                    $bindings[] = $where['value'];
                    break;
                case 'column':
                    $valueColumn = SqlValidator::escapeIdentifier($where['value']);
                    $clause .= "{$column} {$where['operator']} {$valueColumn}";
                    break;
                case 'exists':
                    $not = ($where['not'] ?? false) ? 'NOT ' : '';
                    $subSql = $where['query']->toSql();
                    $clause .= "{$not}EXISTS ({$subSql})";
                    break;
                case 'subquery':
                    $subQuery = $where['query']->toSql();
                    $bindings = array_merge($bindings, $where['query']->getBindings());
                    $clause .= "{$column} {$where['operator']} ({$subQuery})";
                    break;
            }

            $clauses[] = $clause;
        }

        return [implode(' ', $clauses), $bindings];
    }

    public function withTrashed(): self
    {
        $this->softDeleteMode = 'all';
        return $this;
    }

    public function onlyTrashed(): self
    {
        $this->softDeleteMode = 'only';
        return $this;
    }

    public function getSoftDeleteMode(): string
    {
        return $this->softDeleteMode;
    }

    private function __clone() {}
}
