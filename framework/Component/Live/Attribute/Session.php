<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Session
{
    public function __construct(
        public ?string $key = null
    ) {}
}
