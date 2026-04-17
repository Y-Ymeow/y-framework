<?php

declare(strict_types=1);

namespace Framework\Routing;

final class RouteDefinition
{
    /**
     * @param list<string> $methods
     * @param list<string> $parameterNames
     * @param list<string> $middlewares
     */
    public function __construct(
        public readonly string $path,
        public readonly array $methods,
        public readonly mixed $handler,
        public readonly ?string $name = null,
        public readonly string $pattern = '',
        public readonly array $parameterNames = [],
        public readonly array $middlewares = [],
    ) {
    }
}
