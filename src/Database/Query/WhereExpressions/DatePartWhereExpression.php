<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

final class DatePartWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'date_part';

    public function __construct(
        private readonly string $part,
        private readonly string $column,
        private readonly string $operator,
        private readonly mixed $value,
        private readonly string $boolean = 'AND',
    ) {}

    public function getPart(): string
    {
        return $this->part;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
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
