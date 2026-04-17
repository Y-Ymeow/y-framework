<?php

declare(strict_types=1);

namespace Framework\Routing;

final class RoutePattern
{
    /**
     * @return array{pattern: string, parameterNames: list<string>}
     */
    public static function compile(string $path): array
    {
        $parameterNames = [];
        $normalized = rtrim($path, '/') ?: '/';

        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$parameterNames): string {
            $parameterNames[] = $matches[1];

            return '([^/]+)';
        }, $normalized);

        return [
            'pattern' => '#^' . $regex . '$#',
            'parameterNames' => $parameterNames,
        ];
    }
}
