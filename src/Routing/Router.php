<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\NotFoundHttpException;
use Framework\Http\Request;

final class Router
{
    /**
     * @var list<RouteDefinition>
     */
    private array $routes = [];

    /**
     * @param list<RouteDefinition> $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    public function add(RouteDefinition $route): void
    {
        $this->routes[] = $route;
    }

    public function match(Request $request): CompiledRoute
    {
        $uri = rtrim($request->uri(), '/') ?: '/';
        $method = $request->method();

        foreach ($this->routes as $route) {
            if (! in_array($method, $route->methods, true)) {
                continue;
            }

            $path = rtrim($route->path, '/') ?: '/';

            if ($path === $uri) {
                return new CompiledRoute($route);
            }

            if ($route->pattern === '') {
                continue;
            }

            if (! preg_match($route->pattern, $uri, $matches)) {
                continue;
            }

            $parameters = [];

            foreach ($route->parameterNames as $index => $name) {
                $parameters[$name] = $matches[$index + 1] ?? '';
            }

            return new CompiledRoute($route, $parameters);
        }

        throw new NotFoundHttpException('Route not found.');
    }

    /**
     * @param list<array{path: string, methods: list<string>, handler: mixed, name: ?string, pattern?: string, parameterNames?: list<string>, middlewares?: list<string>}> $payload
     */
    public static function fromCompiled(array $payload): self
    {
        $routes = [];

        foreach ($payload as $item) {
            $compiled = isset($item['pattern'], $item['parameterNames'])
                ? ['pattern' => $item['pattern'], 'parameterNames' => $item['parameterNames']]
                : RoutePattern::compile($item['path']);

            $routes[] = new RouteDefinition(
                path: $item['path'],
                methods: $item['methods'],
                handler: $item['handler'],
                name: $item['name'],
                pattern: $compiled['pattern'],
                parameterNames: $compiled['parameterNames'],
                middlewares: $item['middlewares'] ?? [],
            );
        }

        return new self($routes);
    }

    /**
     * @return list<RouteDefinition>
     */
    public function all(): array
    {
        return $this->routes;
    }
}
