<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Persistent
{
    public function __construct(
        public string $driver = 'local',
        public ?string $key = null,
        public ?int $ttl = null,
        public bool $encrypt = false
    ) {}
}
