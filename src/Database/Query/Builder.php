<?php

declare(strict_types=1);

namespace Framework\Database\Query;

use Framework\Database\Contracts\ConnectionInterface;
use Framework\Database\Contracts\QueryBuilderInterface;
use Framework\Database\Query\WhereExpressions\BasicWhereExpression;
use Framework\Database\Query\WhereExpressions\BetweenWhereExpression;
use Framework\Database\Query\WhereExpressions\ColumnWhereExpression;
use Framework\Database\Query\WhereExpressions\DatePartWhereExpression;
use Framework\Database\Query\WhereExpressions\DateWhereExpression;
use Framework\Database\Query\WhereExpressions\ExistsWhereExpression;
use Framework\Database\Query\WhereExpressions\InWhereExpression;
use Framework\Database\Query\WhereExpressions\NullWhereExpression;
use Framework\Database\Query\WhereExpressions\RawWhereExpression;
use Framework\Database\Query\WhereExpressions\WhereExpressionInterface;
use Framework\Support\Collection;

class Builder implements QueryBuilderInterface
{
    private string $from;

    /** @var SelectExpression[] */
    private array $columns = [];

    /** @var WhereExpressionInterface[] */
    private array $wheres = [];

    /** @var JoinClause[] */
    private array $joins = [];

    /** @var OrderExpression[] */
    private array $orders = [];

    /** @var GroupByExpression[] */
    private array $groups = [];

    /** @var HavingExpression[] */
    private array $havings = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private bool $distinct = false;

    private ?string $lock = null;

    private Grammar $grammar;

    public function __construct(
        private readonly ConnectionInterface $connection,
        string $table,
        ?Grammar $grammar = null,
    ) {
        $this->from = $table;
        $this->grammar = $grammar ?? new Grammars\MySqlGrammar();
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getHavings(): array
    {
        return $this->havings;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    public function getLock(): ?string
    {
        return $this->lock;
    }

    // ---- SELECT ----

    public function select(string ...$columns): self
    {
        $this->columns = [];

        foreach ($columns as $column) {
            $parts = preg_split('/\s+as\s+/i', trim($column));
            $col = trim($parts[0]);

            if (isset($parts[1])) {
                $this->columns[] = new SelectExpression($col, trim($parts[1]));
            } else {
                $this->columns[] = new SelectExpression($col);
            }
        }

        return $this;
    }

    public function addSelect(string ...$columns): self
    {
        foreach ($columns as $column) {
            $parts = preg_split('/\s+as\s+/i', trim($column));
            $col = trim($parts[0]);

            if (isset($parts[1])) {
                $this->columns[] = new SelectExpression($col, trim($parts[1]));
            } else {
                $this->columns[] = new SelectExpression($col);
            }
        }

        return $this;
    }

    // ---- WHERE ----

    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = new BasicWhereExpression($column, $operator, $value, $boolean);

        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = new InWhereExpression($column, $values, boolean: 'AND', not: false);

        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = new InWhereExpression($column, $values, boolean: 'AND', not: true);

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = new NullWhereExpression($column, boolean: 'AND', not: false);

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = new NullWhereExpression($column, boolean: 'AND', not: true);

        return $this;
    }

    public function whereLike(string $column, string $pattern): self
    {
        $this->wheres[] = new BasicWhereExpression($column, 'LIKE', $pattern);

        return $this;
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = new BetweenWhereExpression($column, $min, $max, boolean: 'AND', not: false);

        return $this;
    }

    public function whereNotBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = new BetweenWhereExpression($column, $min, $max, boolean: 'AND', not: true);

        return $this;
    }

    public function whereColumn(string $column1, string $operator, string $column2): self
    {
        $this->wheres[] = new ColumnWhereExpression($column1, $operator, $column2);

        return $this;
    }

    public function whereExists(QueryBuilderInterface $query): self
    {
        $this->wheres[] = new ExistsWhereExpression($query, boolean: 'AND', not: false);

        return $this;
    }

    public function whereNotExists(QueryBuilderInterface $query): self
    {
        $this->wheres[] = new ExistsWhereExpression($query, boolean: 'AND', not: true);

        return $this;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = new RawWhereExpression($sql, $bindings, $boolean);

        return $this;
    }

    public function whereDate(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = new DateWhereExpression($column, $operator, $value);

        return $this;
    }

    public function whereDay(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = new DatePartWhereExpression('DAY', $column, $operator, $value);

        return $this;
    }

    public function whereMonth(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = new DatePartWhereExpression('MONTH', $column, $operator, $value);

        return $this;
    }

    public function whereYear(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = new DatePartWhereExpression('YEAR', $column, $operator, $value);

        return $this;
    }

    // ---- JOIN ----

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $allowedTypes = ['INNER', 'LEFT', 'RIGHT', 'CROSS', 'FULL'];
        $joinType = strtoupper($type);

        if (!in_array($joinType, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid join type: {$type}");
        }

        $this->joins[] = new JoinClause($table, $first, $operator, $second, $joinType);

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

    // ---- ORDER BY ----

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        $this->orders[] = new OrderExpression(column: $column, direction: $direction);

        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function orderByRaw(string $sql, array $bindings = []): self
    {
        $this->orders[] = new OrderExpression(
            column: '',
            direction: 'ASC',
            raw: true,
            rawSql: $sql,
            rawBindings: $bindings,
        );

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

    // ---- LIMIT / OFFSET ----

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

    // ---- GROUP BY ----

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->groups[] = new GroupByExpression(expression: $column);
        }

        return $this;
    }

    public function groupByRaw(string $sql, array $bindings = []): self
    {
        $this->groups[] = new GroupByExpression(
            expression: $sql,
            raw: true,
            rawBindings: $bindings,
        );

        return $this;
    }

    // ---- HAVING ----

    public function having(string $column, string $operator, mixed $value): self
    {
        $sql = $this->grammar->wrap($column) . " {$operator} ?";
        $this->havings[] = new HavingExpression(sql: $sql, bindings: [$value]);

        return $this;
    }

    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->havings[] = new HavingExpression(sql: $sql, bindings: $bindings);

        return $this;
    }

    // ---- DISTINCT ----

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    // ---- LOCK ----

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

    // ---- EXECUTION ----

    public function get(): Collection
    {
        $result = $this->grammar->compileSelect($this);
        $rows = $this->connection->query($result['sql'], $result['bindings']);
        $collections = [];

        foreach ($rows as $row) {
            $collections[] = new Collection($row);
        }

        return new Collection($collections);
    }

    public function first(): ?Collection
    {
        $this->limit = 1;
        $result = $this->grammar->compileSelect($this);
        $row = $this->connection->queryOne($result['sql'], $result['bindings']);

        if ($row === null) {
            return null;
        }

        return new Collection($row);
    }

    public function find(mixed $id, string $column = 'id'): ?Collection
    {
        return $this->where($column, $id)->first();
    }

    public function count(string $column = '*'): int
    {
        $select = $column === '*' ? '*' : $this->grammar->wrap($column);
        $savedColumns = $this->columns;
        $this->columns = [new SelectExpression("COUNT({$select}) as _count")];

        $result = $this->grammar->compileSelect($this);
        $row = $this->connection->queryOne($result['sql'], $result['bindings']);

        $this->columns = $savedColumns;

        return (int) ($row['_count'] ?? 0);
    }

    public function sum(string $column): mixed
    {
        $col = $this->grammar->wrap($column);
        $savedColumns = $this->columns;
        $this->columns = [new SelectExpression("SUM({$col}) as _sum")];

        $result = $this->grammar->compileSelect($this);
        $row = $this->connection->queryOne($result['sql'], $result['bindings']);

        $this->columns = $savedColumns;

        return $row['_sum'] ?? 0;
    }

    public function avg(string $column): mixed
    {
        $col = $this->grammar->wrap($column);
        $savedColumns = $this->columns;
        $this->columns = [new SelectExpression("AVG({$col}) as _avg")];

        $result = $this->grammar->compileSelect($this);
        $row = $this->connection->queryOne($result['sql'], $result['bindings']);

        $this->columns = $savedColumns;

        return $row['_avg'] ?? 0;
    }

    public function max(string $column): mixed
    {
        $col = $this->grammar->wrap($column);
        $savedColumns = $this->columns;
        $this->columns = [new SelectExpression("MAX({$col}) as _max")];

        $result = $this->grammar->compileSelect($this);
        $row = $this->connection->queryOne($result['sql'], $result['bindings']);

        $this->columns = $savedColumns;

        return $row['_max'] ?? null;
    }

    public function min(string $column): mixed
    {
        $col = $this->grammar->wrap($column);
        $savedColumns = $this->columns;
        $this->columns = [new SelectExpression("MIN({$col}) as _min")];

        $result = $this->grammar->compileSelect($this);
        $row = $this->connection->queryOne($result['sql'], $result['bindings']);

        $this->columns = $savedColumns;

        return $row['_min'] ?? null;
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

        $this->limit = $perPage;
        $this->offset = $offset;

        $items = $this->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => (int) min($offset + $perPage, $total),
        ];
    }

    // ---- INSERT / UPDATE / DELETE ----

    public function insert(array $data): int
    {
        $result = $this->grammar->compileInsert($this, $data);
        $this->connection->execute($result['sql'], $result['bindings']);

        return (int) $this->connection->getPdo()->lastInsertId();
    }

    public function update(array $data): int
    {
        if (empty($this->wheres)) {
            throw new \RuntimeException('Update without WHERE clause is not allowed');
        }

        $result = $this->grammar->compileUpdate($this, $data);
        $stmt = $this->connection->execute($result['sql'], $result['bindings']);

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new \RuntimeException('Delete without WHERE clause is not allowed');
        }

        $result = $this->grammar->compileDelete($this);
        $stmt = $this->connection->execute($result['sql'], $result['bindings']);

        return $stmt->rowCount();
    }

    // ---- INCREMENT / DECREMENT ----

    public function increment(string $column, int|float $amount = 1): int
    {
        if (empty($this->wheres)) {
            throw new \RuntimeException('Increment without WHERE clause is not allowed');
        }

        $col = $this->grammar->wrap($column);
        $wheres = $this->grammar->compileWheres($this);

        $sql = 'UPDATE ' . $this->grammar->wrap($this->from)
             . " SET {$col} = {$col} + ?"
             . ' WHERE ' . $wheres['sql'];

        $stmt = $this->connection->execute($sql, array_merge([$amount], $wheres['bindings']));

        return $stmt->rowCount();
    }

    public function decrement(string $column, int|float $amount = 1): int
    {
        return $this->increment($column, -$amount);
    }

    // ---- UTILITY ----

    public function toSql(): string
    {
        $result = $this->grammar->compileSelect($this);

        return $result['sql'];
    }

    public function getBindings(): array
    {
        $result = $this->grammar->compileSelect($this);

        return $result['bindings'];
    }

    public function __clone()
    {
        $this->wheres = array_map(fn(WhereExpressionInterface $w) => $w, $this->wheres);
        $this->columns = array_map(fn(SelectExpression $c) => $c, $this->columns);
        $this->joins = array_map(fn(JoinClause $j) => $j, $this->joins);
        $this->orders = array_map(fn(OrderExpression $o) => $o, $this->orders);
        $this->groups = array_map(fn(GroupByExpression $g) => $g, $this->groups);
        $this->havings = array_map(fn(HavingExpression $h) => $h, $this->havings);
    }
}