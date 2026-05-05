<?php

declare(strict_types=1);

namespace Framework\Routing\Attribute;

/**
 * 路由中间件属性
 *
 * 支持在类或方法级别声明中间件，可指定优先级、参数和限制应用范围
 *
 * @example
 * // 基础用法
 * #[Middleware(AuthMiddleware::class)]
 *
 * // 带参数
 * #[Middleware(ThrottleMiddleware::class, params: ['max' => 60, 'decay' => 60])]
 *
 * // 指定优先级（数字越小越先执行，默认 0）
 * #[Middleware(LogMiddleware::class, priority: -10)]
 *
 * // 多个中间件
 * #[Middleware([AuthMiddleware::class, RoleMiddleware::class])]
 *
 * // 限制应用范围（仅应用到指定方法）
 * #[Middleware(AuthMiddleware::class, only: ['store', 'update', 'destroy'])]
 *
 * // 排除某些方法
 * #[Middleware(AuthMiddleware::class, except: ['index', 'show'])]
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(
        public string|array $middleware = [],
        public int $priority = 0,
        public array $params = [],
        public array $only = [],
        public array $except = [],
    ) {}

    /**
     * 检查中间件是否应该应用到当前方法
     */
    public function appliesTo(string $methodName): bool
    {
        if (!empty($this->only) && !in_array($methodName, $this->only, true)) {
            return false;
        }

        if (!empty($this->except) && in_array($methodName, $this->except, true)) {
            return false;
        }

        return true;
    }
}
