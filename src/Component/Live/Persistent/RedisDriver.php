<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

use Framework\Redis\RedisManager;

class RedisDriver implements PersistentDriverInterface
{
    private ?\Redis $redis = null;

    public function __construct()
    {
        try {
            if (class_exists(RedisManager::class)) {
                $this->redis = app()->make(RedisManager::class)->connection();
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
        return 'live_persistent:' . $key;
    }
}
