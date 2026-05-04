<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

use Framework\Http\Request;

class CookieDriver implements PersistentDriverInterface
{
    private static array $cache = [];

    public function __construct()
    {
        if (empty(self::$cache)) {
            $this->loadFromCookies();
        }
    }

    private function loadFromCookies(): void
    {
        if (function_exists('app')) {
            try {
                $request = app()->make(Request::class);
                self::$cache = $request->cookies->all();
            } catch (\Throwable $e) {
                self::$cache = $_COOKIE ?? [];
            }
        } else {
            self::$cache = $_COOKIE ?? [];
        }
    }

    public function get(string $key): mixed
    {
        return self::$cache[$key] ?? null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        self::$cache[$key] = $value;

        $valueStr = is_string($value) ? $value : serialize($value);
        $expires = $ttl ? time() + $ttl : time() + (86400 * 30);

        if (!headers_sent()) {
            setcookie($key, $valueStr, $expires, '/', '', false, true);
        }

        return true;
    }

    public function forget(string $key): bool
    {
        unset(self::$cache[$key]);

        if (!headers_sent()) {
            setcookie($key, '', time() - 3600, '/', '', false, true);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }
}
