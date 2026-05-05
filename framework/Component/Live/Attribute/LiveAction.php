<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveAction
{
    public function __construct(
        public ?string $name = null,
    ) {}
}
