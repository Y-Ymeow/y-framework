<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveListener
{
    public function __construct(
        public string $event,
        public int $priority = 0
    ) {}
}
