<?php

declare(strict_types=1);

namespace Framework\Cache\Drivers;

use Framework\Cache\Contracts\LockInterface;
use Framework\Cache\Contracts\StoreInterface;
use Framework\Cache\Lock\RedisLock;
use Framework\Cache\Support\KeyValidator;
use Framework\Cache\Support\TtlHelper;

class RedisDriver implements StoreInterface
{
    private \Redis $redis;
    private string $prefix;

    public function __construct(array $config)
    {
        $this->redis = new \Redis();

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $password = $config['password'] ?? null;
        $database = $config['database'] ?? 0;

        $this->redis->connect($host, (int) $port);

        if ($password !== null) {
            $this->redis->auth($password);
        }

        if ($database > 0) {
            $this->redis->select((int) $database);
        }

        $this->prefix = $config['prefix'] ?? '';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        KeyValidator::validate($key);

        $value = $this->redis->get($this->prefixed($key));
        return $value !== false ? unserialize($value) : $default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        KeyValidator::validate($key);

        $prefixed = $this->prefixed($key);
        $serialized = serialize($value);

        if ($ttl !== null) {
            $seconds = TtlHelper::resolveTtlSeconds($ttl);
            return $this->redis->setex($prefixed, $seconds, $serialized);
        }

        return $this->redis->set($prefixed, $serialized);
    }

    public function delete(string $key): bool
    {
        KeyValidator::validate($key);

        return $this->redis->del($this->prefixed($key)) > 0;
    }

    public function has(string $key): bool
    {
        KeyValidator::validate($key);

        return $this->redis->exists($this->prefixed($key)) > 0;
    }

    public function clear(): bool
    {
        $pattern = $this->prefix . '*';
        $keys = $this->redis->keys($pattern);

        if (empty($keys)) {
            return true;
        }

        $this->redis->del(...$keys);
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        KeyValidator::validateMultiple($keys);

        $prefixedKeys = [];
        $keyMap = [];
        foreach ($keys as $key) {
            $prefixed = $this->prefixed($key);
            $prefixedKeys[] = $prefixed;
            $keyMap[$prefixed] = $key;
        }

        $values = $this->redis->mGet($prefixedKeys);

        $result = [];
        foreach ($prefixedKeys as $i => $prefixed) {
            $originalKey = $keyMap[$prefixed];
            $value = $values[$i];
            $result[$originalKey] = $value !== false ? unserialize($value) : $default;
        }

        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        if ($ttl !== null) {
            foreach ($values as $key => $value) {
                $this->set((string) $key, $value, $ttl);
            }
            return true;
        }

        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefixed((string) $key)] = serialize($value);
        }

        return $this->redis->mSet($prefixedValues);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        KeyValidator::validateMultiple($keys);

        $prefixedKeys = [];
        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefixed((string) $key);
        }

        if (empty($prefixedKeys)) {
            return true;
        }

        return $this->redis->del(...$prefixedKeys) > 0;
    }

    public function increment(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        return $this->redis->incrBy($this->prefixed($key), $step);
    }

    public function decrement(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        return $this->redis->decrBy($this->prefixed($key), $step);
    }

    public function lock(string $key, int $seconds = 0): LockInterface
    {
        return new RedisLock($key, $seconds, $this->redis, $this->prefix . 'lock:');
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getRedis(): \Redis
    {
        return $this->redis;
    }

    private function prefixed(string $key): string
    {
        return $this->prefix . $key;
    }
}
