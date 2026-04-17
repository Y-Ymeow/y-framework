<?php

declare(strict_types=1);

namespace Framework\Database;

final class QueryBuilder
{
    /**
     * @var list<string>
     */
    private array $columns = ['*'];

    /**
     * @var list<string>
     */
    private array $wheres = [];

    /**
     * @var list<string>
     */
    private array $orders = [];

    /**
     * @var array<int|string, mixed>
     */
    private array $bindings = [];

    private ?int $limit = null;
    private int $bindingIndex = 0;

    public function __construct(
        private readonly Connection $connection,
        private readonly string $table,
    ) {
    }

    public function select(string ...$columns): self
    {
        if ($columns !== []) {
            $this->columns = $columns;
        }

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $placeholder = 'p' . $this->bindingIndex++;
        $this->wheres[] = sprintf('%s %s :%s', $column, $operator, $placeholder);
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    public function whereRaw(string $expression, array $bindings = []): self
    {
        $this->wheres[] = $expression;
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $column . ' ' . $direction;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->bindings);
    }

    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit(1);

        return $clone->connection->first($clone->toSql(), $clone->bindings);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $placeholders[] = ':' . $column;
            $bindings[$column] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        return $this->connection->execute($sql, $bindings);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): bool
    {
        $sets = [];
        $bindings = $this->bindings;

        foreach ($data as $column => $value) {
            $placeholder = 'u_' . $column;
            $sets[] = $column . ' = :' . $placeholder;
            $bindings[$placeholder] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s%s', $this->table, implode(', ', $sets), $this->compileWhere());

        return $this->connection->execute($sql, $bindings);
    }

    public function delete(): bool
    {
        $sql = sprintf('DELETE FROM %s%s', $this->table, $this->compileWhere());

        return $this->connection->execute($sql, $this->bindings);
    }

    public function toSql(): string
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->columns), $this->table);
        $sql .= $this->compileWhere();

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        return $sql;
    }

    private function compileWhere(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $this->wheres);
    }
}
