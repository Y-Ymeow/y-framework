<?php

declare(strict_types=1);

namespace Framework\Routing;

final class RouteCompiler
{
    /**
     * @param list<RouteDefinition> $routes
     */
    public function compile(array $routes): string
    {
        $payload = [];

        foreach ($routes as $route) {
            $payload[] = [
                'path' => $route->path,
                'methods' => $route->methods,
                'handler' => $route->handler,
                'name' => $route->name,
                'pattern' => $route->pattern,
                'parameterNames' => $route->parameterNames,
                'middlewares' => $route->middlewares,
            ];
        }

        return "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
    }
}
