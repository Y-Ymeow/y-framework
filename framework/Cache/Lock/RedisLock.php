<?php

declare(strict_types=1);

namespace Framework\Cache\Lock;

class RedisLock extends Lock
{
    public function __construct(
        string $key,
        int $seconds = 0,
        private readonly ?\Redis $redis = null,
        private readonly string $prefix = 'lock:',
    ) {
        parent::__construct($key, $seconds);
    }

    public function acquire(): bool
    {
        if ($this->redis === null) {
            return false;
        }

        $lockKey = $this->prefix . $this->key;

        if ($this->isOwnedByCurrentProcess()) {
            if ($this->seconds > 0) {
                $this->redis->expire($lockKey, $this->seconds);
            }
            return true;
        }

        if ($this->seconds > 0) {
            $acquired = $this->redis->set($lockKey, $this->owner, ['NX', 'EX' => $this->seconds]);
        } else {
            $acquired = $this->redis->set($lockKey, $this->owner, ['NX']);
        }

        return (bool) $acquired;
    }

    public function release(): bool
    {
        if ($this->redis === null) {
            return false;
        }

        if (!$this->isOwnedByCurrentProcess()) {
            return false;
        }

        $lockKey = $this->prefix . $this->key;

        $script = <<<'LUA'
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;

        return (bool) $this->redis->eval($script, [$lockKey, $this->owner], 1);
    }

    public function isOwnedByCurrentProcess(): bool
    {
        if ($this->redis === null) {
            return false;
        }

        $lockKey = $this->prefix . $this->key;
        $value = $this->redis->get($lockKey);

        return $value === $this->owner;
    }
}
