<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Foundation\AppEnvironment;
use Framework\Http\Response\Response;
use Framework\Http\Response\ResponseSender;

/**
 * StreamedResponse 回调式流式响应
 *
 * 通过回调函数输出响应内容，适用于文件下载、大文件传输等场景。
 * send() 时自动清除输出缓冲并关闭 session，确保回调中的 echo/flush 直接到达客户端。
 *
 * @http-category Response
 * @http-since 2.0
 *
 * @http-example
 * return new StreamedResponse(function () {
 *     $stream = fopen('/path/to/file', 'rb');
 *     while (!feof($stream)) {
 *         echo fread($stream, 8192);
 *         flush();
 *     }
 *     fclose($stream);
 * }, 200, ['Content-Type' => 'application/octet-stream']);
 * @http-example-end
 */
class StreamedResponse extends Response
{
    protected $callback;
    protected bool $streamed = false;

    /**
     * 创建回调式流式响应
     * @param callable $callback 输出回调函数
     * @param int $status HTTP 状态码
     * @param array $headers 响应头
     */
    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        $this->callback = $callback;
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->statusText = self::$statusTexts[$status] ?? 'Unknown';
    }

    /**
     * 发送流式响应（仅执行一次）
     * @return void
     */
    public function send(): void
    {
        if ($this->streamed) {
            return;
        }

        $this->streamed = true;

        (new ResponseSender())->sendHeaders($this->statusCode, $this->statusText, $this->headers);

        if (AppEnvironment::isWasm()) {
            ob_start();
            call_user_func($this->callback);
            $this->content = ob_get_clean();
            echo $this->content;
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        call_user_func($this->callback);
    }

    /**
     * 获取响应内容（会执行回调）
     * @return string
     */
    public function getContent(): string
    {
        if (!$this->streamed) {
            ob_start();
            call_user_func($this->callback);
            return ob_get_clean();
        }

        return $this->content;
    }

    /**
     * 设置输出回调
     * @param callable $callback 回调函数
     * @return static
     */
    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }
}
