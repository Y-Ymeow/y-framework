<?php

declare(strict_types=1);

namespace Framework\Routing;

use Closure;

/**
 * 路由静态入口
 */
final class Route
{
    /** @var list<RouteRegistrar> */
    private static array $stack = [];

    /** @var list<RouteDefinition> */
    private static array $routes = [];

    public static function group(array $attributes, Closure $callback): void
    {
        $current = self::getRegistrar();
        $registrar = new RouteRegistrar($current->mergeAttributes($current->getAttributes(), $attributes));
        
        self::$stack[] = $registrar;
        $callback();
        array_pop(self::$stack);

        foreach ($registrar->getRoutes() as $route) {
            self::addDefinition($route);
        }
    }

    public static function get(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return self::addRoute(['GET'], $path, $handler, $name);
    }

    public static function post(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return self::addRoute(['POST'], $path, $handler, $name);
    }

    public static function put(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return self::addRoute(['PUT'], $path, $handler, $name);
    }

    public static function patch(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return self::addRoute(['PATCH'], $path, $handler, $name);
    }

    public static function delete(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return self::addRoute(['DELETE'], $path, $handler, $name);
    }

    public static function any(string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        return self::addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], $path, $handler, $name);
    }

    private static function addRoute(array|string $methods, string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        $route = self::getRegistrar()->addRoute($methods, $path, $handler, $name);
        if (empty(self::$stack)) {
            self::$routes[] = $route;
        }
        return $route;
    }

    private static function addDefinition(RouteDefinition $route): void
    {
        if (empty(self::$stack)) {
            self::$routes[] = $route;
        }
    }

    private static function getRegistrar(): RouteRegistrar
    {
        if (empty(self::$stack)) {
            return new RouteRegistrar();
        }
        return self::$stack[count(self::$stack) - 1];
    }

    /**
     * 获取所有定义的路由并清空内部状态
     * @return list<RouteDefinition>
     */
    public static function drain(): array
    {
        $routes = self::$routes;
        self::$routes = [];
        self::$stack = [];
        return $routes;
    }
}
