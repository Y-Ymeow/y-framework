<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;

/**
 * 中间件管理器
 *
 * 负责全局/路由组中间件的注册、优先级排序和执行
 *
 * 中间件接口约定：
 * - 同步中间件: handle(Request $request, callable $next): Response
 * - 异步中间件: handle(Request $request, callable $next): Response
 */
class MiddlewareManager
{
    private static ?self $instance = null;

    /**
     * 全局中间件 [class => ['priority' => int, 'params' => array]]
     */
    private array $globalMiddleware = [];

    /**
     * 路由组中间件 [group => [class => ['priority' => int, 'params' => array]]]
     */
    private array $groupMiddleware = [];

    /**
     * 中间件别名 [alias => class]
     */
    private array $aliases = [
        'auth' => \Framework\Http\Middleware\Authenticate::class,
        'guest' => \Framework\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Framework\Http\Middleware\ThrottleRequests::class,
        'csrf' => \Framework\Http\Middleware\VerifyCsrfToken::class,
        'trim' => \Framework\Http\Middleware\TrimStrings::class,
        'json' => \Framework\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * 注册中间件别名
     */
    public function alias(string $alias, string $class): self
    {
        $this->aliases[$alias] = $class;
        return $this;
    }

    /**
     * 解析中间件名称（支持别名）
     */
    public function resolve(string $name): string
    {
        return $this->aliases[$name] ?? $name;
    }

    /**
     * 注册全局中间件
     *
     * @param string|array $middleware 中间件类名或别名
     * @param int $priority 优先级（越小越先执行）
     * @param array $params 中间件参数
     */
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

    /**
     * 注册路由组中间件
     *
     * @param string $group 组名称
     * @param string|array $middleware 中间件类名或别名
     * @param int $priority 优先级
     * @param array $params 中间件参数
     */
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

    /**
     * 获取按优先级排序的中间件列表
     *
     * @param string|null $group 可选的组名称
     * @return array [['class' => string, 'params' => array], ...]
     */
    public function getMiddleware(?string $group = null): array
    {
        $merged = $this->globalMiddleware;

        if ($group !== null && isset($this->groupMiddleware[$group])) {
            $merged = array_merge($merged, $this->groupMiddleware[$group]);
        }

        // 按优先级排序
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

    /**
     * 执行中间件管道
     *
     * @param Request $request
     * @param callable $destination 目标处理器
     * @param array $additionalMiddleware 额外中间件（来自路由属性）
     * @param string|null $group 组名称
     * @return Response
     */
    public function pipe(Request $request, callable $destination, array $additionalMiddleware = [], ?string $group = null): Response
    {
        $middleware = $this->getMiddleware($group);

        // 合并路由级别的中间件
        foreach ($additionalMiddleware as $mw) {
            if (is_array($mw)) {
                // ['class' => X, 'params' => [...]] 或 [X, 'param1', 'param2']
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

        // 从后往前构建管道
        $handler = $destination;
        foreach (array_reverse($middleware) as $mw) {
            $handler = $this->createMiddlewareHandler($mw['class'], $handler, $mw['params']);
        }

        return $handler($request);
    }

    /**
     * 创建单个中间件处理器
     */
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
