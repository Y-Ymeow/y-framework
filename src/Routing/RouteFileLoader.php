<?php

declare(strict_types=1);

namespace Framework\Routing;

final class RouteFileLoader
{
    /**
     * @param list<string> $files
     * @return list<RouteDefinition>
     */
    public function load(array $files): array
    {
        $routes = [];

        foreach ($files as $file) {
            /** @var mixed $loaded */
            $loaded = require $file;

            // 支持 Route::get() 风格的定义
            foreach (Route::drain() as $route) {
                $routes[] = $route;
            }

            if (! is_array($loaded)) {
                continue;
            }

            foreach ($loaded as $route) {
                if ($route instanceof RouteDefinition) {
                    $routes[] = $route;
                }
            }
        }

        return $routes;
    }
}
