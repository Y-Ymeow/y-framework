<?php

declare(strict_types=1);

namespace Framework\Http;

class Cookie
{
    public static function set(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        $options = [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];
        setcookie($name, $value, $options);
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function remove(string $name, string $path = '/', string $domain = ''): void
    {
        setcookie($name, '', time() - 3600, $path, $domain);
        unset($_COOKIE[$name]);
    }

    public static function forever(string $name, string $value, string $path = '/', string $domain = '', bool $secure = true, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        self::set($name, $value, time() + 5 * 365 * 24 * 60 * 60, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function forget(string $name, string $path = '/', string $domain = ''): void
    {
        self::remove($name, $path, $domain);
    }
}
