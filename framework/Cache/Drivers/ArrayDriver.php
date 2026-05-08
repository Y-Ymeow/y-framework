<?php

declare(strict_types=1);

namespace Framework\Cache\Drivers;

use Framework\Cache\Contracts\LockInterface;
use Framework\Cache\Contracts\StoreInterface;
use Framework\Cache\Lock\ArrayLock;
use Framework\Cache\Support\KeyValidator;
use Framework\Cache\Support\TtlHelper;

class ArrayDriver implements StoreInterface
{
    private array $data = [];
    private array $expires = [];
    private string $prefix;

    public function __construct(array $config = [])
    {
        $this->prefix = $config['prefix'] ?? '';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        KeyValidator::validate($key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->data[$key];
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        KeyValidator::validate($key);

        $this->data[$key] = $value;
        $this->expires[$key] = TtlHelper::resolveTtl($ttl);
        return true;
    }

    public function delete(string $key): bool
    {
        KeyValidator::validate($key);

        unset($this->data[$key], $this->expires[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->data[$key])) {
            return false;
        }

        if (isset($this->expires[$key]) && $this->expires[$key] !== null && $this->expires[$key] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function clear(): bool
    {
        $this->data = [];
        $this->expires = [];
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        KeyValidator::validateMultiple($keys);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }
        return true;
    }

    public function increment(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        $current = $this->has($key) ? (int) $this->data[$key] : 0;
        $this->data[$key] = $current + $step;
        return $this->data[$key];
    }

    public function decrement(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        $current = $this->has($key) ? (int) $this->data[$key] : 0;
        $this->data[$key] = $current - $step;
        return $this->data[$key];
    }

    public function lock(string $key, int $seconds = 0): LockInterface
    {
        return new ArrayLock($key, $seconds);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
