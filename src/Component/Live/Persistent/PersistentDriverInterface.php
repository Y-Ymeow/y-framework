<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

interface PersistentDriverInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    public function forget(string $key): bool;

    public function has(string $key): bool;
}
