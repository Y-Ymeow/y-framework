<?php

declare(strict_types=1);

namespace Framework\Component\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Rule
{
    public function __construct(
        public string $rules,
    ) {}
}
