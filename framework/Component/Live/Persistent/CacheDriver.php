<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

class CacheDriver implements PersistentDriverInterface
{
    public function get(string $key): mixed
    {
        if (!function_exists('cache')) {
            return null;
        }

        try {
            return cache()->get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!function_exists('cache')) {
            return false;
        }

        try {
            if ($ttl !== null) {
                cache()->set($key, $value, new \DateInterval("PT{$ttl}S"));
            } else {
                cache()->set($key, $value);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function forget(string $key): bool
    {
        if (!function_exists('cache')) {
            return false;
        }

        try {
            return cache()->delete($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        if (!function_exists('cache')) {
            return false;
        }

        try {
            return cache()->has($key);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
