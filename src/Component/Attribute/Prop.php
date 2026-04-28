<?php

declare(strict_types=1);

namespace Framework\Component\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Prop
{
    public function __construct(
        public mixed $default = null,
    ) {}
}
