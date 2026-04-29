<?php

declare(strict_types=1);

namespace Framework\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class RouteGroup
{
    public function __construct(
        public string $prefix = '',
        public array $middleware = [],
        public string $name = '',
    ) {}
}
