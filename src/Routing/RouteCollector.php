<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Routing\Attribute\Route as RouteAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

final class RouteCollector
{
    /**
     * @param list<class-string> $classes
     * @param list<string> $files
     * @return list<RouteDefinition>
     */
    public function collect(array $classes = [], array $files = []): array
    {
        $routes = [];

        // 1. 扫描类中的 Attribute (控制器模式)
        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(RouteAttribute::class) as $attribute) {
                    $routes[] = $this->createDefinition($attribute->newInstance(), [$class, $method->getName()]);
                }
            }
        }

        // 2. 扫描文件中的函数 Attribute (函数式 Action 模式)
        foreach ($files as $file) {
            // 我们需要先 require 文件来让函数定义生效
            require_once $file;
            
            // 这里简单处理：假设文件名和函数名有某种关联，或者我们扫描当前定义的全局函数
            // 更好的做法是解析 AST，但为了性能和简单，我们直接获取当前定义的所有函数
            // 注意：这在开发环境下配合路由缓存是没问题的
        }

        // 获取所有带有 Route 属性的全局函数
        $functions = get_defined_functions()['user'];
        foreach ($functions as $functionName) {
            $reflection = new ReflectionFunction($functionName);
            foreach ($reflection->getAttributes(RouteAttribute::class) as $attribute) {
                $routes[] = $this->createDefinition($attribute->newInstance(), $functionName);
            }
        }

        return $routes;
    }

    private function createDefinition(RouteAttribute $route, mixed $handler): RouteDefinition
    {
        $compiled = RoutePattern::compile($route->path);
        return new RouteDefinition(
            path: $route->path,
            methods: array_map(static fn (string $item): string => strtoupper($item), $route->methods),
            handler: $handler,
            name: $route->name,
            pattern: $compiled['pattern'],
            parameterNames: $compiled['parameterNames'],
            middlewares: $route->middlewares,
        );
    }
}
