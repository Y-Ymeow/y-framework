<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

interface CollectorInterface
{
    public function collect(array $items): void;
    public function getCollected(): array;
    public function clear(): void;
    public function count(): int;
}
