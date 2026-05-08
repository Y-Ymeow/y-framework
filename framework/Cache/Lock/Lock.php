<?php

declare(strict_types=1);

namespace Framework\Cache\Lock;

use Framework\Cache\Contracts\LockInterface;
use Framework\Cache\Exception\LockTimeoutException;

abstract class Lock implements LockInterface
{
    protected string $owner;

    public function __construct(
        protected string $key,
        protected int $seconds = 0,
    ) {
        $this->owner = $this->generateOwner();
    }

    public function block(int $seconds = 0): bool
    {
        $start = time();
        $timeout = $seconds > 0 ? $seconds : 0;

        while (true) {
            if ($this->acquire()) {
                return true;
            }

            if ($timeout > 0 && (time() - $start) >= $timeout) {
                throw new LockTimeoutException($this->key, $timeout);
            }

            usleep(250_000);
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    private function generateOwner(): string
    {
        return sprintf('%s:%s', getmypid() ?: '0', bin2hex(random_bytes(8)));
    }
}
