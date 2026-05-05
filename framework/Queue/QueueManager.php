<?php

declare(strict_types=1);

namespace Framework\Queue;

use Framework\Queue\Job;

class QueueManager
{
    private static array $config = [];
    private static ?QueueDriverInterface $driver = null;

    public static function init(array $config = []): void
    {
        self::$config = $config;
    }

    public static function driver(?string $driver = null): QueueDriverInterface
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        $driverName = $driver ?? (self::$config['default'] ?? 'sync');

        self::$driver = match ($driverName) {
            'database' => new DatabaseDriver(),
            'redis' => new RedisDriver(
                dsn: self::$config['redis']['dsn'] ?? 'redis://localhost:6379',
                prefix: self::$config['redis']['prefix'] ?? 'queue:'
            ),
            'sync' => new SyncDriver(),
            default => throw new \InvalidArgumentException("Unknown queue driver: {$driverName}"),
        };

        return self::$driver;
    }

    public static function connection(string $driver): QueueDriverInterface
    {
        return self::driver($driver);
    }

    public static function push(string|callable $job, array $data = [], ?string $queue = null, int $delay = 0): bool
    {
        $job = Job::make($job, $data);
        if ($queue !== null) {
            $job->setQueue($queue);
        }
        if ($delay > 0) {
            $job->setDelay($delay);
        }
        return self::driver()->push($job);
    }

    public static function pushMany(array $jobs, ?string $queue = null): int
    {
        $count = 0;
        foreach ($jobs as $job) {
            if (is_string($job)) {
                $count += (int) self::push($job);
            } elseif (is_array($job)) {
                $count += (int) self::push(
                    $job['class'] ?? '',
                    $job['data'] ?? [],
                    $job['queue'] ?? $queue,
                    $job['delay'] ?? 0
                );
            } elseif ($job instanceof Job) {
                $count += (int) self::driver()->push($job);
            }
        }
        return $count;
    }

    public static function pop(?string $queue = null): ?Job
    {
        return self::driver()->pop($queue);
    }

    public static function size(?string $queue = null): int
    {
        return self::driver()->size($queue);
    }

    public static function clear(?string $queue = null): bool
    {
        return self::driver()->clear($queue);
    }

    public static function getDriver(): QueueDriverInterface
    {
        return self::driver();
    }
}
