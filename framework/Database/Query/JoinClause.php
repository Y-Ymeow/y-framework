<?php

declare(strict_types=1);

namespace Framework\Database\Query;

final class JoinClause
{
    public function __construct(
        public readonly string $table,
        public readonly string $first,
        public readonly string $operator,
        public readonly string $second,
        public readonly string $type = 'INNER',
    ) {}

    public function getType(): string
    {
        return strtoupper($this->type);
    }
}