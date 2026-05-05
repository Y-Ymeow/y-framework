<?php

declare(strict_types=1);

namespace Framework\Database\Query;

final class GroupByExpression
{
    public function __construct(
        public readonly string $expression,
        public readonly bool $raw = false,
        public readonly array $rawBindings = [],
    ) {}
}