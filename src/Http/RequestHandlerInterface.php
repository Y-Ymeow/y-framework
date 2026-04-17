<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * 仿 PSR-15 风格的请求处理器接口
 */
interface RequestHandlerInterface
{
    /**
     * 处理请求并返回响应
     */
    public function handle(Request $request): Response;
}
