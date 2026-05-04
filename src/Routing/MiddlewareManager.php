<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class MiddlewareManager
{
    private array $globalMiddleware = [];
    private array $groupMiddleware = [];
    private array $aliases = [
        'auth' => \Framework\Http\Middleware\Authenticate::class,
        'guest' => \Framework\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Framework\Http\Middleware\ThrottleRequests::class,
        'csrf' => \Framework\Http\Middleware\VerifyCsrfToken::class,
        'trim' => \Framework\Http\Middleware\TrimStrings::class,
        'json' => \Framework\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    public function alias(string $alias, string $class): self
    {
        $this->aliases[$alias] = $class;
        return $this;
    }

    public function resolve(string $name): string
    {
        return $this->aliases[$name] ?? $name;
    }

    public function use(string|array $middleware, int $priority = 0, array $params = []): self
    {
        foreach ((array)$middleware as $mw) {
            $class = $this->resolve($mw);
            $this->globalMiddleware[$class] = [
                'priority' => $priority,
                'params' => $params,
            ];
        }
        return $this;
    }

    public function group(string $group, string|array $middleware, int $priority = 0, array $params = []): self
    {
        foreach ((array)$middleware as $mw) {
            $class = $this->resolve($mw);
            $this->groupMiddleware[$group][$class] = [
                'priority' => $priority,
                'params' => $params,
            ];
        }
        return $this;
    }

    public function getMiddleware(?string $group = null): array
    {
        $merged = $this->globalMiddleware;

        if ($group !== null && isset($this->groupMiddleware[$group])) {
            $merged = array_merge($merged, $this->groupMiddleware[$group]);
        }

        uasort($merged, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $result = [];
        foreach ($merged as $class => $config) {
            $result[] = [
                'class' => $class,
                'params' => $config['params'],
            ];
        }

        return $result;
    }

    public function pipe(Request $request, callable $destination, array $additionalMiddleware = [], ?string $group = null): Response
    {
        $middleware = $this->getMiddleware($group);

        foreach ($additionalMiddleware as $mw) {
            if (is_array($mw)) {
                if (isset($mw['class'])) {
                    $middleware[] = $mw;
                } elseif (isset($mw[0])) {
                    $middleware[] = [
                        'class' => $this->resolve($mw[0]),
                        'params' => array_slice($mw, 1),
                    ];
                }
            } else {
                $middleware[] = [
                    'class' => $this->resolve($mw),
                    'params' => [],
                ];
            }
        }

        $handler = $destination;
        foreach (array_reverse($middleware) as $mw) {
            $handler = $this->createMiddlewareHandler($mw['class'], $handler, $mw['params']);
        }

        return $handler($request);
    }

    private function createMiddlewareHandler(string $middlewareClass, callable $next, array $params): callable
    {
        return function (Request $request) use ($middlewareClass, $next, $params) {
            if (!class_exists($middlewareClass)) {
                throw new \RuntimeException("Middleware [{$middlewareClass}] not found");
            }

            $instance = app()->make($middlewareClass);

            if (!method_exists($instance, 'handle')) {
                throw new \RuntimeException("Middleware [{$middlewareClass}] does not have a handle method");
            }

            return $instance->handle($request, $next, ...$params);
        };
    }
}
