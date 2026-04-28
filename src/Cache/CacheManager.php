<?php

declare(strict_types=1);

namespace Framework\Cache;

class CacheManager implements \Psr\SimpleCache\CacheInterface
{
    private array $stores = [];
    private string $default;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'file';
    }

    public function store(?string $name = null): \Psr\SimpleCache\CacheInterface
    {
        $name = $name ?: $this->default;

        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->createDriver($name);
        }

        return $this->stores[$name];
    }

    private function createDriver(string $name): \Psr\SimpleCache\CacheInterface
    {
        $config = $this->config['stores'][$name] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        return match ($config['driver']) {
            'file' => new FileCache($config['path']),
            'redis' => new RedisCache($config),
            'memory', 'array' => new ArrayCache(),
            default => throw new \InvalidArgumentException("Cache driver [{$config['driver']}] is not supported."),
        };
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->store()->delete($key);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function clear(): bool
    {
        return $this->store()->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->store()->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->store()->deleteMultiple($keys);
    }

    public function remember(string $key, \Closure $callback, \DateInterval|int|null $ttl = null): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }
}

class FileCache implements \Psr\SimpleCache\CacheInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/');
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path . '/' . md5($key);
        if (!is_file($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data === false) {
            return $default;
        }

        if (isset($data['expires']) && $data['expires'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $expires = $this->resolveTtl($ttl);

        $data = [
            'value' => $value,
            'expires' => $expires,
        ];

        $file = $this->path . '/' . md5($key);
        return file_put_contents($file, serialize($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->path . '/' . md5($key);
        if (is_file($file)) {
            return unlink($file);
        }
        return true;
    }

    public function has(string $key): bool
    {
        $file = $this->path . '/' . md5($key);
        if (!is_file($file)) {
            return false;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data === false) {
            return false;
        }

        if (isset($data['expires']) && $data['expires'] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->path . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    private function resolveTtl(\DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            return $now->add($ttl)->getTimestamp();
        }

        return time() + $ttl;
    }
}

class ArrayCache implements \Psr\SimpleCache\CacheInterface
{
    private array $data = [];
    private array $expires = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->data[$key];
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->data[$key] = $value;
        $this->expires[$key] = $this->resolveTtl($ttl);
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->data[$key], $this->expires[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->data[$key])) {
            return false;
        }

        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
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
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    private function resolveTtl(\DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            return $now->add($ttl)->getTimestamp();
        }

        return time() + $ttl;
    }
}

class RedisCache implements \Psr\SimpleCache\CacheInterface
{
    private \Redis $redis;
    private string $prefix;

    public function __construct(array $config)
    {
        $this->redis = new \Redis();
        $this->redis->connect($config['host'], $config['port'] ?? 6379);
        $this->prefix = $config['prefix'] ?? '';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->prefix . $key);
        return $value !== false ? unserialize($value) : $default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $serialized = serialize($value);
        if ($ttl !== null) {
            $seconds = $this->resolveTtlSeconds($ttl);
            return $this->redis->setex($this->prefix . $key, $seconds, $serialized);
        }
        return $this->redis->set($this->prefix . $key, $serialized);
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($this->prefix . $key) > 0;
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($this->prefix . $key) > 0;
    }

    public function clear(): bool
    {
        $keys = $this->redis->keys($this->prefix . '*');
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    private function resolveTtlSeconds(\DateInterval|int|null $ttl): int
    {
        if ($ttl === null) {
            return 3600;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            return $now->add($ttl)->getTimestamp() - time();
        }

        return $ttl;
    }
}
