<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

use Framework\Support\Facades\Cache;

class CacheDriver implements PersistentDriverInterface
{
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return Cache::put($key, $value, $ttl);
        }

        return Cache::forever($key, $value);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }
}
