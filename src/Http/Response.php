<?php

declare(strict_types=1);

namespace Framework\Http;

use Dom\Element;
use Framework\Component\Live\LiveComponent;
use Framework\Foundation\AppEnvironment;
use Framework\UX\UXComponent;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * HTTP 响应封装
 *
 * 封装 Symfony Response，提供便捷的静态工厂方法。
 * 自动适配运行环境（Web / CLI / WASM）。
 *
 * ## 环境适配
 *
 * ### Web 模式（默认）
 * - send() 调用 Symfony Response 发送完整 HTTP 响应
 * - 包含状态码、Headers、Body
 * - Content-Type: text/html 或 application/json
 *
 * ### WASM/Tauri 模式
 * - send() 通过 JS Bridge 返回数据给前端
 * - 不设置 HTTP Headers（WebView 不需要）
 * - json() 输出可直接被 Tauri invoke() 接收
 * - html() 自动使用 Document 的 partial 模式
 *
 * @since 1.0.0
 */
class Response
{
    private SymfonyResponse $sfResponse;

    public function __construct(string|SymfonyResponse $content = '', int $status = 200, array $headers = [])
    {
        if ($content instanceof SymfonyResponse) {
            $this->sfResponse = $content;
        } else {
            $this->sfResponse = new SymfonyResponse($content, $status, $headers);
        }
    }

    public static function fromSymfony(SymfonyResponse $response): self
    {
        return new self($response);
    }

    /**
     * JSON 响应
     *
     * Web 和 WASM 通用，直接返回 JSON 字符串。
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $response = new self('', $status, $headers);
        $response->sfResponse->headers->set('Content-Type', 'application/json');
        $response->sfResponse->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * HTML 响应 — 自动适配环境
     *
     * **Web 模式**: 输出完整 HTML 文档
     * **WASM 模式**: 自动切换为 partial 模式，只输出内容部分
     *
     * Tauri 前端接收后注入到 WebView：
     * ```typescript
     * const result = await invoke('handle_request', { path: '/dashboard' });
     * document.getElementById('app-content').innerHTML = result.content;
     * ```
     */
    public static function html(mixed $html, int $status = 200, array $headers = []): self
    {
        // 创建 Document（WASM 环境下自动切换为 partial 模式）
        $doc = \Framework\View\Document\Document::make();
        $content = $doc->main($html)->render();

        $response = new self($content, $status, $headers);

        if (AppEnvironment::supportsHeaders()) {
            $response->sfResponse->headers->set('Content-Type', 'text/html; charset=utf-8');
        }

        return $response;
    }

    /**
     * WASM 专用响应 — 返回结构化数据
     *
     * 适用于 Tauri JS Bridge 直接调用，
     * 返回 JSON 格式的 { content, title, assets } 结构。
     *
     * ```typescript
     * const result = await invoke('render_page', { path: '/dashboard' });
     * // result: { content: "<main>...</main>", title: "仪表盘", assets: [...] }
     * ```
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
     * 重定向响应
     *
     * WASM 环境下重定向通过 JS Bridge 处理，
     * 返回特殊标记让前端执行导航。
     */
    public static function redirect(string $url, int $status = 302): self
    {
        if (AppEnvironment::isWasm()) {
            // WASM 环境：返回重定向指令，由前端 JS 执行
            return self::json([
                '_redirect' => true,
                'url' => $url,
                'status' => $status,
            ]);
        }

        // 正常 HTTP 重定向
        $response = new self('', $status);
        $response->sfResponse->headers->set('Location', $url);
        return $response;
    }

    /**
     * 发送响应
     *
     * 根据环境选择发送方式：
     * - Web: Symfony Response.send()
     * - WASM: 输出到 stdout（JS Bridge 捕获）
     */
    public function send(): void
    {
        if (AppEnvironment::isWasm()) {
            // WASM 环境：直接输出内容，由 Tauri JS Bridge 捕获
            echo $this->getContent();
            return;
        }

        // 正常 HTTP 响应
        $this->sfResponse->send();
    }

    public function getSfResponse(): SymfonyResponse
    {
        return $this->sfResponse;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->sfResponse->headers->set($key, $value);
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->sfResponse->setStatusCode($code);
        return $this;
    }

    public function getStatus(): int
    {
        return $this->sfResponse->getStatusCode();
    }

    public function getStatusCode(): int
    {
        return $this->sfResponse->getStatusCode();
    }

    public function getContent(): string
    {
        return $this->sfResponse->getContent();
    }
}
