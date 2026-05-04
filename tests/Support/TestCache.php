<?php

declare(strict_types=1);

namespace Tests\Support;

class TestCache
{
    private static ?self $instance = null;
    private array $store = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        if (self::$instance) {
            self::$instance->clear();
        }
        self::$instance = null;
    }

    public function get($key, $default = null)
    {
        return $this->store[$key] ?? $default;
    }

    public function put($key, $value): void
    {
        $this->store[$key] = $value;
    }

    public function forget($key): void
    {
        unset($this->store[$key]);
    }

    public function remember(string $key, int $ttl, callable $callback)
    {
        if (isset($this->store[$key])) {
            return $this->store[$key];
        }
        return $this->store[$key] = $callback();
    }

    public function clear(): void
    {
        $this->store = [];
    }

    public function has(string $key): bool
    {
        return isset($this->store[$key]);
    }
}
