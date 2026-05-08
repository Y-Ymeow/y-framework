<?php

declare(strict_types=1);

namespace Framework\Cache\Events;

readonly class CacheMissed
{
    public function __construct(
        public string $key,
        public string $store,
    ) {
    }
}
