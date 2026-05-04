<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Foundation\AppEnvironment;

/**
 * Response HTTP 响应
 *
 * 框架核心 HTTP 响应类，支持 JSON、HTML、WASM、重定向等响应类型。
 * 自动适配 Web/WASM 双模式运行环境。
 *
 * @http-category Response
 * @http-since 2.0
 *
 * @http-example
 * // JSON 响应
 * return Response::json(['success' => true, 'data' => $items]);
 *
 * // HTML 响应
 * return Response::html($element);
 *
 * // 重定向
 * return Response::redirect('/dashboard');
 * @http-example-end
 */
class Response
{
    protected string $content = '';
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $statusText = '';
    protected array $flashData = [];

    private static array $statusTexts = [
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

    /**
     * 创建 HTTP 响应
     * @param string $content 响应内容
     * @param int $status HTTP 状态码
     * @param array $headers 响应头
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->statusText = self::$statusTexts[$status] ?? 'Unknown';
    }

    /**
     * 创建 JSON 响应
     * @param mixed $data 数据
     * @param int $status HTTP 状态码
     * @param array $headers 响应头
     * @return static
     * @http-example Response::json(['success' => true], 201)
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE), $status, $headers);
    }

    /**
     * 创建 HTML 响应
     * @param mixed $html HTML 内容或 Element 对象
     * @param int $status HTTP 状态码
     * @param array $headers 响应头
     * @return static
     * @http-example Response::html(Element::make('h1')->text('Hello'))
     */
    public static function html(mixed $html, int $status = 200, array $headers = []): self
    {
        $doc = \Framework\View\Document\Document::make();
        $content = $doc->main($html)->render();

        if (AppEnvironment::supportsHeaders()) {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        return new self($content, $status, $headers);
    }

    /**
     * 创建 WASM 模式响应
     * @param mixed $html HTML 内容
     * @param string $title 页面标题
     * @param int $status HTTP 状态码
     * @return static
     */
    public static function wasm(mixed $html, string $title = '', int $status = 200): self
    {
        $doc = \Framework\View\Document\Document::make($title);
        $doc->main($html);

        return self::json([
            'content' => $doc->render(),
            'title' => $title,
            'mode' => AppEnvironment::isWasm() ? 'partial' : 'full',
            'status' => $status,
        ]);
    }

    /**
     * 创建重定向响应
     * @param string $url 目标 URL
     * @param int $status HTTP 状态码
     * @return static
     * @http-example Response::redirect('/login', 302)
     */
    public static function redirect(string $url, int $status = 302): self
    {
        if (AppEnvironment::isWasm()) {
            return self::json([
                '_redirect' => true,
                'url' => $url,
                'status' => $status,
            ]);
        }

        return new self('', $status, ['Location' => $url]);
    }

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
            \Framework\Http\Session::flash('_flash', $this->flashData);
        }

        return $this;
    }

    /**
     * 发送响应到客户端
     * @return void
     */
    public function send(): void
    {
        if (AppEnvironment::isWasm()) {
            echo $this->content;
            return;
        }

        $this->sendHeaders();
        echo $this->content;
    }

    /** @internal */
    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        header("{$protocol} {$this->statusCode} {$this->statusText}");

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }
    }

    /**
     * 设置响应头
     * @param string $key 头名称
     * @param string $value 头值
     * @return static
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * 获取响应头
     * @param string $key 头名称
     * @param string|null $default 默认值
     * @return string|null
     */
    public function getHeader(string $key, ?string $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * 获取所有响应头
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 设置 HTTP 状态码
     * @param int $code 状态码
     * @return static
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        $this->statusText = self::$statusTexts[$code] ?? 'Unknown';
        return $this;
    }

    /**
     * 获取 HTTP 状态码
     * @return int
     */
    public function getStatus(): int
    {
        return $this->statusCode;
    }

    /**
     * 获取 HTTP 状态码（别名）
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 获取响应内容
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * 设置响应内容
     * @param string $content 内容
     * @return static
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
}
