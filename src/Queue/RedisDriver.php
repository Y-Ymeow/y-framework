<?php

declare(strict_types=1);

namespace Framework\Queue;

class RedisDriver implements QueueDriverInterface
{
    private \Redis $redis;
    private string $prefix;

    public function __construct(string $dsn = 'redis://localhost:6379', string $prefix = 'queue:')
    {
        $this->prefix = $prefix;
        $this->redis = new \Redis();
        
        $parsed = parse_url($dsn);
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 6379;
        
        $this->redis->connect($host, $port);
        
        if (isset($parsed['pass'])) {
            $this->redis->auth($parsed['pass']);
        }
    }

    public function push(Job $job): bool
    {
        try {
            $key = $this->prefix . 'queue:' . $job->queue;
            $this->redis->rPush($key, serialize($job));
            return true;
        } catch (\Throwable $e) {
            error_log("Queue push failed: " . $e->getMessage());
            return false;
        }
    }

    public function pop(?string $queue = null): ?Job
    {
        $queue = $queue ?? 'default';
        $key = $this->prefix . 'queue:' . $queue;
        
        $data = $this->redis->lPop($key);
        if ($data === false) {
            return null;
        }

        return unserialize($data);
    }

    public function size(?string $queue = null): int
    {
        $queue = $queue ?? 'default';
        $key = $this->prefix . 'queue:' . $queue;
        return (int) $this->redis->lLen($key);
    }

    public function clear(?string $queue = null): bool
    {
        $queue = $queue ?? 'default';
        $key = $this->prefix . 'queue:' . $queue;
        $this->redis->del($key);
        return true;
    }
}
