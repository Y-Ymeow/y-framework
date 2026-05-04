<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class State
{
    public function __construct(
        public mixed $default = null,
        public bool $frontendEditable = true,
    ) {}
}
