<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

final class BetweenWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'between';

    public function __construct(
        private readonly string $column,
        private readonly mixed $min,
        private readonly mixed $max,
        private readonly string $boolean = 'AND',
        private readonly bool $not = false,
    ) {}

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getMin(): mixed
    {
        return $this->min;
    }

    public function getMax(): mixed
    {
        return $this->max;
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
