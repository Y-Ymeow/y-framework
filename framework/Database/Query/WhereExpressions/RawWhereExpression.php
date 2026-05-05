<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

final class RawWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'raw';

    public function __construct(
        private readonly string $sql,
        private readonly array $bindings = [],
        private readonly string $boolean = 'AND',
    ) {}

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getBoolean(): string
    {
        return $this->boolean;
    }

    public function getType(): string
    {
        return self::TYPE;
    }
}
