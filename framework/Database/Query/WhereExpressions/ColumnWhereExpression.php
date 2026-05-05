<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

final class ColumnWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'column';

    public function __construct(
        private readonly string $column1,
        private readonly string $operator,
        private readonly string $column2,
        private readonly string $boolean = 'AND',
    ) {}

    public function getColumn1(): string
    {
        return $this->column1;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getColumn2(): string
    {
        return $this->column2;
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
