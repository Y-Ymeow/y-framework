<?php

declare(strict_types=1);

namespace Framework\Cache;

use Framework\Cache\Contracts\LockInterface;
use Framework\Cache\Contracts\StoreInterface;
use Framework\Cache\Drivers\ArrayDriver;
use Framework\Cache\Drivers\FileDriver;
use Framework\Cache\Drivers\RedisDriver;
use Framework\Cache\Events\CacheHit;
use Framework\Cache\Events\CacheMissed;
use Framework\Cache\Events\KeyForgotten;
use Framework\Cache\Events\KeyWritten;
use Framework\Cache\Tag\TagSet;
use Framework\Cache\Tag\TaggedCache;
use Psr\SimpleCache\CacheInterface;

class CacheManager implements CacheInterface
{
    private array $stores = [];
    private string $default;
    private array $config;
    private ?object $dispatcher = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'file';
    }

    public function setDispatcher(object $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function store(?string $name = null): StoreInterface
    {
        $name = $name ?: $this->default;

        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->createDriver($name);
        }

        return $this->stores[$name];
    }

    public function tags(array|string $names): TaggedCache
    {
        $store = $this->store();
        $tagSet = new TagSet($store, $names);
        return new TaggedCache($store, $tagSet);
    }

    public function lock(string $key, int $seconds = 0): LockInterface
    {
        return $this->store()->lock($key, $seconds);
    }

    public function increment(string $key, int $step = 1): int
    {
        return $this->store()->increment($key, $step);
    }

    public function decrement(string $key, int $step = 1): int
    {
        return $this->store()->decrement($key, $step);
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

    public function rememberForever(string $key, \Closure $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value);
        return $value;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    public function putMany(array $values, \DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->store()->get($key, $default);

        if ($value !== $default) {
            $this->dispatchEvent(new CacheHit($key, $value, $this->default));
        } else {
            $this->dispatchEvent(new CacheMissed($key, $this->default));
        }

        return $value;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $result = $this->store()->set($key, $value, $ttl);

        if ($result) {
            $this->dispatchEvent(new KeyWritten($key, $value, is_int($ttl) ? $ttl : null, $this->default));
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $result = $this->store()->delete($key);

        if ($result) {
            $this->dispatchEvent(new KeyForgotten($key, $this->default));
        }

        return $result;
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

    public function getDefaultStore(): string
    {
        return $this->default;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function forgetStore(string $name): void
    {
        unset($this->stores[$name]);
    }

    private function createDriver(string $name): StoreInterface
    {
        $config = $this->config['stores'][$name] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        $driverConfig = array_merge($config, [
            'prefix' => $this->config['prefix'] ?? '',
        ]);

        return match ($config['driver']) {
            'file' => new FileDriver($driverConfig),
            'redis' => new RedisDriver($driverConfig),
            'array', 'memory' => new ArrayDriver($driverConfig),
            default => throw new \InvalidArgumentException("Cache driver [{$config['driver']}] is not supported."),
        };
    }

    private function dispatchEvent(object $event): void
    {
        if ($this->dispatcher !== null && method_exists($this->dispatcher, 'dispatch')) {
            $this->dispatcher->dispatch($event);
        }
    }
}
