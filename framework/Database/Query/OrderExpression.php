<?php

declare(strict_types=1);

namespace Framework\Database\Query;

final class OrderExpression
{
    public function __construct(
        public readonly string $column,
        public readonly string $direction = 'ASC',
        public readonly bool $raw = false,
        public readonly string $rawSql = '',
        public readonly array $rawBindings = [],
    ) {}
}