<?php

declare(strict_types=1);

namespace Framework\Queue;

class SyncDriver implements QueueDriverInterface
{
    public function push(Job $job): bool
    {
        try {
            $job->handle();
            return true;
        } catch (\Throwable $e) {
            $job->failed($e);
            return false;
        }
    }

    public function pop(?string $queue = null): ?Job
    {
        return null;
    }

    public function size(?string $queue = null): int
    {
        return 0;
    }

    public function clear(?string $queue = null): bool
    {
        return true;
    }
}
