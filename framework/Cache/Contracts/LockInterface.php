<?php

declare(strict_types=1);

namespace Framework\Cache\Contracts;

interface LockInterface
{
    public function acquire(): bool;

    public function release(): bool;

    public function block(int $seconds = 0): bool;

    public function isOwnedByCurrentProcess(): bool;

    public function getKey(): string;
}
