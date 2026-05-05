<?php

declare(strict_types=1);

namespace Framework\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $path = '',
        public string|array $methods = ['GET'],
        public string $name = '',
        public array $middleware = [],
    ) {}
}
