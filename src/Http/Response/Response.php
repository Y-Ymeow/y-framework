<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Http\Session\Session;

/**
 * Response 核心类
 *
 * 职责：持有响应数据（内容、状态码、头、Flash），组合 ResponseSender 发送。
 * 子类：JsonResponse、HtmlResponse、RedirectResponse
 */
class Response
{
    protected string $content = '';
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $statusText = '';
    protected array $flashData = [];

    protected static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->statusText = self::$statusTexts[$status] ?? 'Unknown';
    }

    // ── 工厂方法 ──

    public static function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    public static function html(mixed $html, int $status = 200, array $headers = []): HtmlResponse
    {
        return new HtmlResponse($html, $status, $headers);
    }

    public static function wasm(mixed $html, string $title = '', int $status = 200): JsonResponse
    {
        $doc = \Framework\View\Document\Document::make($title);
        $doc->main($html);

        $data = [
            'content' => $doc->render(),
            'title' => $title,
            'mode' => \Framework\Foundation\AppEnvironment::isWasm() ? 'partial' : 'full',
            'status' => $status,
        ];

        return new JsonResponse($data, $status);
    }

    public static function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    // ── 链式方法 ──

    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->flashData[$k] = $v;
            }
        } else {
            $this->flashData[$key] = $value;
        }

        if (!empty($this->flashData)) {
            session()->set('_flash', $this->flashData);
        }

        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function getHeader(string $key, ?string $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        $this->statusText = self::$statusTexts[$code] ?? 'Unknown';
        return $this;
    }

    public function getStatus(): int
    {
        return $this->statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    // ── 发送 ──

    public function send(): void
    {
        $sender = new ResponseSender();
        $sender->send($this->content, $this->statusCode, $this->statusText, $this->headers);
    }
}

