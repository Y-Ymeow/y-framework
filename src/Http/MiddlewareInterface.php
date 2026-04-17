<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * 仿 PSR-15 风格的中间件接口
 */
interface MiddlewareInterface
{
    /**
     * 处理请求并返回响应
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
