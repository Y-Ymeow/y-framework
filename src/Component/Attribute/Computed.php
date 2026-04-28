<?php

declare(strict_types=1);

namespace Framework\Component\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Computed
{
    public function __construct(
        public ?string $name = null,
    ) {}
}
