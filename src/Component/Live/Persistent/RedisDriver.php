<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

class RedisDriver implements PersistentDriverInterface
{
    /** @var \Redis|null */
    private $redis = null;

    public function __construct()
    {
        try {
            if (class_exists(\Redis::class)) {
                $redis = new \Redis();
                $host = config('redis.default.host', '127.0.0.1');
                $port = (int) config('redis.default.port', 6379);
                $redis->connect($host, $port);
                $this->redis = $redis;
            }
        } catch (\Throwable $e) {
            // Redis 不可用
        }
    }

    public function get(string $key): mixed
    {
        if (!$this->redis) {
            return null;
        }

        $value = $this->redis->get($this->prefix($key));
        if ($value === false) {
            return null;
        }

        return @unserialize($value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->redis) {
            return false;
        }

        $serialized = serialize($value);
        $fullKey = $this->prefix($key);

        if ($ttl !== null) {
            return $this->redis->setex($fullKey, $ttl, $serialized);
        }

        return $this->redis->set($fullKey, $serialized);
    }

    public function forget(string $key): bool
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->del($this->prefix($key)) > 0;
    }

    public function has(string $key): bool
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->exists($this->prefix($key)) > 0;
    }

    private function prefix(string $key): string
    {
        $prefix = config('redis.default.prefix', '');
        return $prefix . 'live_persistent:' . $key;
    }
}
