<?php

declare(strict_types=1);

namespace Framework\Routing;

function route(array|string $methods, string $path, mixed $handler, ?string $name = null): RouteDefinition
{
    $normalizedMethods = is_array($methods) ? $methods : [$methods];
    $compiled = RoutePattern::compile($path);

    return new RouteDefinition(
        path: $path,
        methods: array_map(static fn (string $method): string => strtoupper($method), $normalizedMethods),
        handler: $handler,
        name: $name,
        pattern: $compiled['pattern'],
        parameterNames: $compiled['parameterNames'],
    );
}

function get(string $path, mixed $handler, ?string $name = null): RouteDefinition
{
    return route(['GET'], $path, $handler, $name);
}

function post(string $path, mixed $handler, ?string $name = null): RouteDefinition
{
    return route(['POST'], $path, $handler, $name);
}

function put(string $path, mixed $handler, ?string $name = null): RouteDefinition
{
    return route(['PUT'], $path, $handler, $name);
}

function patch(string $path, mixed $handler, ?string $name = null): RouteDefinition
{
    return route(['PATCH'], $path, $handler, $name);
}

function delete(string $path, mixed $handler, ?string $name = null): RouteDefinition
{
    return route(['DELETE'], $path, $handler, $name);
}
