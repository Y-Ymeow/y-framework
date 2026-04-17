<?php

declare(strict_types=1);

namespace Framework\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    public function __construct(
        public ?string $name = null,
        public bool $isPrimary = false,
        public bool $autoIncrement = false
    ) {}
}
