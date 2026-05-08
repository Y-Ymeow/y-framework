<?php

declare(strict_types=1);

namespace Framework\Cache\Exception;

class LockTimeoutException extends CacheException
{
    private string $lockKey;
    private int $timeout;

    public function __construct(string $lockKey, int $timeout, ?\Throwable $previous = null)
    {
        $this->lockKey = $lockKey;
        $this->timeout = $timeout;

        parent::__construct(
            sprintf('Lock acquisition for key [%s] timed out after %d seconds.', $lockKey, $timeout),
            0,
            $previous
        );
    }

    public function getLockKey(): string
    {
        return $this->lockKey;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
