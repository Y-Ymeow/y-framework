<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Foundation\AppEnvironment;

/**
 * 响应发送器
 *
 * 职责：将响应发送到客户端（HTTP 输出 / WASM 输出）
 */
class ResponseSender
{
    /**
     * 发送响应
     */
    public function send(string $content, int $statusCode, string $statusText, array $headers): void
    {
        if (AppEnvironment::isWasm()) {
            echo $content;
            return;
        }

        $this->sendHeaders($statusCode, $statusText, $headers);
        echo $content;
    }

    /**
     * 发送 HTTP 状态码和头
     */
    public function sendHeaders(int $statusCode, string $statusText, array $headers): void
    {
        if (headers_sent()) {
            return;
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        header("{$protocol} {$statusCode} {$statusText}");

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}", true);
        }
    }
}