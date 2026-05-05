<?php

declare(strict_types=1);

namespace Framework\Database\Query;

final class SelectExpression
{
    public function __construct(
        public readonly string $column,
        public readonly ?string $alias = null,
    ) {}

    public function toSql(): string
    {
        if ($this->alias !== null) {
            return "{$this->column} AS {$this->alias}";
        }
        return $this->column;
    }
}