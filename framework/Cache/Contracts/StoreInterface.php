<?php

declare(strict_types=1);

namespace Framework\Cache\Contracts;

use Psr\SimpleCache\CacheInterface;

interface StoreInterface extends CacheInterface
{
    public function increment(string $key, int $step = 1): int;

    public function decrement(string $key, int $step = 1): int;

    public function lock(string $key, int $seconds = 0): LockInterface;

    public function getPrefix(): string;
}
