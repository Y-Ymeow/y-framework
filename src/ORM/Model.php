<?php

declare(strict_types=1);

namespace Framework\ORM;

use Framework\Database\QueryBuilder;

abstract class Model
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        protected array $attributes = [],
    ) {
    }

    public static function query(): QueryBuilder
    {
        return table(static::tableName());
    }

    public static function find(int|string $id, string $key = 'id'): ?static
    {
        $row = static::query()->where($key, '=', $id)->first();

        return $row === null ? null : new static($row);
    }

    public static function all(): array
    {
        return array_map(static fn (array $row): static => new static($row), static::query()->get());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    protected static function tableName(): string
    {
        if (defined('static::TABLE')) {
            /** @var string */
            return static::TABLE;
        }

        $parts = explode('\\', static::class);
        $name = end($parts);

        return strtolower((string) $name) . 's';
    }
}
