<?php

declare(strict_types=1);

namespace Framework\Routing;

use Closure;

/**
 * 路由注册助手，处理分组和流式调用
 */
final class RouteRegistrar
{
    private array $attributes = [
        'prefix' => '',
        'middleware' => [],
        'namespace' => '',
    ];

    /** @var list<RouteDefinition> */
    private array $routes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * 定义一个路由分组
     */
    public function group(array $attributes, Closure $callback): void
    {
        $newAttributes = $this->mergeAttributes($this->attributes, $attributes);
        $registrar = new RouteRegistrar($newAttributes);
        
        // 临时将当前正在加载的路由转移到子注册器
        // 实际上在函数式模式下，我们需要一个全局状态来捕获注册的路由
        $callback($registrar);
        
        foreach ($registrar->getRoutes() as $route) {
            $this->routes[] = $route;
        }
    }

    /**
     * 注册路由
     */
    public function addRoute(array|string $methods, string $path, mixed $handler, ?string $name = null): RouteDefinition
    {
        $normalizedMethods = is_array($methods) ? $methods : [$methods];
        
        $path = '/' . trim($this->attributes['prefix'], '/') . '/' . trim($path, '/');
        $path = '/' . trim($path, '/');
        
        // 如果 handler 是字符串且有 namespace 前缀
        if (is_string($handler) && !empty($this->attributes['namespace'])) {
            $handler = trim($this->attributes['namespace'], '\\') . '\\' . ltrim($handler, '\\');
        }

        $compiled = RoutePattern::compile($path);

        $route = new RouteDefinition(
            path: $path,
            methods: array_map(static fn (string $method): string => strtoupper($method), $normalizedMethods),
            handler: $handler,
            name: $name,
            pattern: $compiled['pattern'],
            parameterNames: $compiled['parameterNames'],
            middlewares: $this->attributes['middleware']
        );

        $this->routes[] = $route;
        return $route;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function mergeAttributes(array $current, array $new): array
    {
        $prefix = trim($current['prefix'], '/') . '/' . trim($new['prefix'] ?? '', '/');
        $middleware = array_merge($current['middleware'], $new['middleware'] ?? []);
        $namespace = !empty($new['namespace'] ?? '') 
            ? trim($current['namespace'], '\\') . '\\' . trim($new['namespace'], '\\')
            : $current['namespace'];

        return [
            'prefix' => $prefix,
            'middleware' => $middleware,
            'namespace' => $namespace,
        ];
    }
}
