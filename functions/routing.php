<?php

declare(strict_types=1);

use Framework\Routing\RouteDefinition;

if (! function_exists('route')) {
    function route(array|string $methods, string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return Framework\Routing\route($methods, $path, $handler, $name);
    }
}

if (! function_exists('get')) {
    function get(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return Framework\Routing\get($path, $handler, $name);
    }
}

if (! function_exists('post')) {
    function post(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return Framework\Routing\post($path, $handler, $name);
    }
}

if (! function_exists('put')) {
    function put(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return Framework\Routing\put($path, $handler, $name);
    }
}

if (! function_exists('patch')) {
    function patch(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return Framework\Routing\patch($path, $handler, $name);
    }
}

if (! function_exists('delete')) {
    function delete(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return Framework\Routing\delete($path, $handler, $name);
    }
}
