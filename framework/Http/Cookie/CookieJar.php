<?php

declare(strict_types=1);

namespace Framework\Http\Cookie;

class CookieJar
{
    private array $cookies = [];
    private array $queued = [];

    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $name => $value) {
            $this->cookies[$name] = Cookie::fromHeaderValue((string)$name, (string)$value);
        }
    }

    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    public function get(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name]?->getValue() ?? $default;
    }

    public function all(): array
    {
        $result = [];
        foreach ($this->cookies as $name => $cookie) {
            $result[$name] = $cookie->getValue();
        }
        return $result;
    }

    public function set(Cookie $cookie): void
    {
        $this->queued[$cookie->getName()] = $cookie;
    }

    public function setValue(string $name, string $value, array $options = []): void
    {
        $this->set(Cookie::session($name, $value, $options));
    }

    public function forever(string $name, string $value, ?int $lifetime = null, array $options = []): void
    {
        $this->set(Cookie::forever($name, $value, $lifetime, $options));
    }

    public function remove(string $name, array $options = []): void
    {
        $this->set(Cookie::expired($name, $options));
    }

    public function forget(string $name, array $options = []): void
    {
        $this->remove($name, $options);
    }

    public function getQueued(): array
    {
        return $this->queued;
    }

    public function hasQueued(): bool
    {
        return $this->queued !== [];
    }

    public function flush(): void
    {
        $this->queued = [];
    }

    public function send(callable $emitter): void
    {
        foreach ($this->queued as $cookie) {
            $emitter($cookie->toHeaderValue());
        }
    }

    public static function setCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        setcookie($name, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);
    }

    public static function getCookie(string $name, ?string $default = null): ?string
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function hasCookie(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function removeCookie(string $name, string $path = '/', string $domain = ''): void
    {
        setcookie($name, '', time() - 3600, $path, $domain);
        unset($_COOKIE[$name]);
    }

    public static function foreverCookie(string $name, string $value, string $path = '/', string $domain = '', bool $secure = true, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        self::setCookie($name, $value, time() + 5 * 365 * 24 * 60 * 60, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function forgetCookie(string $name, string $path = '/', string $domain = ''): void
    {
        self::removeCookie($name, $path, $domain);
    }
}
