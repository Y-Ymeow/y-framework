<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

use Framework\Database\Contracts\QueryBuilderInterface;

final class NestedWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'nested';

    public function __construct(
        private readonly QueryBuilderInterface $query,
        private readonly string $boolean = 'AND',
    ) {}

    public function getQuery(): QueryBuilderInterface
    {
        return $this->query;
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
