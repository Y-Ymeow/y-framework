<?php

declare(strict_types=1);

namespace Framework\Cache\Events;

readonly class KeyWritten
{
    public function __construct(
        public string $key,
        public mixed $value,
        public int|null $ttl,
        public string $store,
    ) {
    }
}
