<?php

declare(strict_types=1);

namespace Framework\Cache\Events;

readonly class CacheHit
{
    public function __construct(
        public string $key,
        public mixed $value,
        public string $store,
    ) {
    }
}
