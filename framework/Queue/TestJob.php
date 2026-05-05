<?php

declare(strict_types=1);

namespace Framework\Queue;

class TestJob
{
    public function handle(array $data = []): void
    {
        // Do nothing, just for testing
    }
}
