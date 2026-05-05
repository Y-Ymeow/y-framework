<?php

declare(strict_types=1);

namespace Framework\Database\Query;

final class HavingExpression
{
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings = [],
    ) {}
}