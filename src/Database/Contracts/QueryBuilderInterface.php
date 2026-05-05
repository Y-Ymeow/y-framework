<?php

declare(strict_types=1);

namespace Framework\Database\Contracts;

use Framework\Support\Collection;

interface QueryBuilderInterface
{
    public function select(string ...$columns): self;

    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self;

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self;

    public function whereIn(string $column, array $values): self;

    public function whereNull(string $column): self;

    public function whereNotNull(string $column): self;

    public function whereLike(string $column, string $pattern): self;

    public function whereBetween(string $column, mixed $min, mixed $max): self;

    public function whereNotBetween(string $column, mixed $min, mixed $max): self;

    public function whereColumn(string $column1, string $operator, string $column2): self;

    public function whereExists(QueryBuilderInterface $query): self;

    public function whereNotExists(QueryBuilderInterface $query): self;

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self;

    public function whereDate(string $column, string $operator, mixed $value): self;

    public function whereDay(string $column, string $operator, mixed $value): self;

    public function whereMonth(string $column, string $operator, mixed $value): self;

    public function whereYear(string $column, string $operator, mixed $value): self;

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self;

    public function leftJoin(string $table, string $first, string $operator, string $second): self;

    public function rightJoin(string $table, string $first, string $operator, string $second): self;

    public function orderBy(string $column, string $direction = 'ASC'): self;

    public function orderByDesc(string $column): self;

    public function orderByRaw(string $sql, array $bindings = []): self;

    public function latest(string $column = 'created_at'): self;

    public function oldest(string $column = 'created_at'): self;

    public function limit(int $limit): self;

    public function offset(int $offset): self;

    public function groupBy(string ...$columns): self;

    public function groupByRaw(string $sql, array $bindings = []): self;

    public function having(string $column, string $operator, mixed $value): self;

    public function havingRaw(string $sql, array $bindings = []): self;

    public function distinct(bool $distinct = true): self;

    public function lock(?string $lock = 'FOR UPDATE'): self;

    public function sharedLock(): self;

    public function lockForUpdate(): self;

    public function get(): Collection;

    public function first(): ?Collection;

    public function find(mixed $id, string $column = 'id'): ?Collection;

    public function count(string $column = '*'): int;

    public function sum(string $column): mixed;

    public function avg(string $column): mixed;

    public function max(string $column): mixed;

    public function min(string $column): mixed;

    public function exists(): bool;

    public function doesntExist(): bool;

    public function paginate(int $perPage = 15, int $page = 1): array;

    public function insert(array $data): int;

    public function update(array $data): int;

    public function delete(): int;

    public function increment(string $column, int|float $amount = 1): int;

    public function decrement(string $column, int|float $amount = 1): int;

    public function toSql(): string;

    public function getBindings(): array;
}