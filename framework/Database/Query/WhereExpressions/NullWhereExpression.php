<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

final class NullWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'null';

    public function __construct(
        private readonly string $column,
        private readonly string $boolean = 'AND',
        private readonly bool $not = false,
    ) {}

    public function getColumn(): string
    {
        return $this->column;
    }

    public function isNot(): bool
    {
        return $this->not;
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
