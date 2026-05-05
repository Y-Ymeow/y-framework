<?php

declare(strict_types=1);

namespace Framework\Queue;

interface QueueDriverInterface
{
    public function push(Job $job): bool;
    public function pop(?string $queue = null): ?Job;
    public function size(?string $queue = null): int;
    public function clear(?string $queue = null): bool;
}
