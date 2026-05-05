<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

/**
 * 中间件接口
 *
 * 所有中间件实现此接口，职责单一明确。
 * 替代旧的"无接口、随意 handle 签名"模式。
 */
interface MiddlewareInterface
{
    /**
     * 处理请求
     *
     * @param Request $request 当前请求
     * @param callable $next 下一个中间件 / 路由处理器
     * @param mixed ...$params 额外参数（由路由系统传入）
     * @return Response
     */
    public function handle(Request $request, callable $next, mixed ...$params): Response;
}

