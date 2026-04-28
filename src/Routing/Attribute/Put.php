<?php

declare(strict_types=1);

namespace Framework\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Put
{
    public function __construct(
        public string $path = '',
        public string $name = '',
        public array $middleware = [],
    ) {}
}
