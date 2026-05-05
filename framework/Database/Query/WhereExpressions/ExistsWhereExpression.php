<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

use Framework\Database\Contracts\QueryBuilderInterface;

final class ExistsWhereExpression implements WhereExpressionInterface
{
    public const TYPE = 'exists';

    public function __construct(
        private readonly QueryBuilderInterface $query,
        private readonly string $boolean = 'AND',
        private readonly bool $not = false,
    ) {}

    public function getQuery(): QueryBuilderInterface
    {
        return $this->query;
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
