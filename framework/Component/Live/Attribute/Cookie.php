<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Cookie
{
    public function __construct(
        public ?string $key = null,
        public int $minutes = 1440,
        public ?string $path = null,
        public ?string $domain = null,
        public ?bool $secure = null,
        public bool $httpOnly = true
    ) {}
}
