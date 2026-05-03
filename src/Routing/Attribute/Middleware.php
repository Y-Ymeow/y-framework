<?php

declare(strict_types=1);

namespace Framework\Routing\Attribute;

/**
 * 路由中间件属性
 *
 * 支持在类或方法级别声明中间件，可指定优先级和参数
 *
 * @example
 * // 基础用法
 * #[Middleware(AuthMiddleware::class)]
 *
 * // 带参数
 * #[Middleware([ThrottleMiddleware::class, 'max' => 60, 'decay' => 60])]
 *
 * // 指定优先级（数字越小越先执行，默认 0）
 * #[Middleware(LogMiddleware::class, priority: -10)]
 *
 * // 多个中间件
 * #[Middleware([AuthMiddleware::class, RoleMiddleware::class])]
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(
        public string|array $middleware = [],
        public int $priority = 0,
        public array $params = [],
    ) {}
}
