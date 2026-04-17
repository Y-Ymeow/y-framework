<?php

declare(strict_types=1);

namespace Framework\Routing;

final class CompiledRoute
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public readonly RouteDefinition $route,
        public readonly array $parameters = [],
    ) {
    }
}
