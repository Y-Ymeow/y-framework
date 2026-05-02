<?php

declare(strict_types=1);

namespace Framework\View\Document;

use Framework\Foundation\AppEnvironment;
use Framework\Http\Session;
use Framework\View\Base\Element;

/**
 * HTML 文档构建器
 *
 * 构建完整的 HTML 文档或内容片段，自动适配运行环境：
 *
 * - **Web 环境** (PHP-FPM/Apache): 输出完整 HTML 文档
 *   `<!DOCTYPE html><html>...<head>...<body>...</body></html>`
 *
 * - **WASM 环境** (Tauri): 只输出内容部分
 *   Tauri 的 WebView 已有外壳，只需 `<main>` 内容
 *   通过 JS Bridge 将内容注入到 WebView
 *
 * ## 使用方式
 *
 * ### Web 模式（默认）
 * ```php
 * $doc = Document::make('页面标题')
 *     ->css('/assets/app.css')
 *     ->js('/assets/app.js')
 *     ->header($nav->render())
 *     ->main($content)
 *     ->footer($footer);
 * echo $doc;  // 完整 HTML 文档
 * ```
 *
 * ### WASM/Tauri 模式
 * ```php
 * // 方式1: 自动检测（推荐）
 * $doc = Document::make('标题')->main($content);
 * echo $doc->render();  // WASM 下自动只输出 <main>
 *
 * // 方式2: 手动指定模式
 * $doc = Document::make('标题')
 *     ->mode('partial')  // 强制使用片段模式
 *     ->main($content);
 *
 * // 方式3: 获取 JSON 格式（Tauri JS Bridge 调用）
 * echo $doc->toJson();  // { html: "...", title: "..." }
 * ```
 *
 * @since 1.0.0
 */
class Document
{
    /**
     * 渲染模式
     */
    public const MODE_FULL = 'full';
    public const MODE_PARTIAL = 'partial';
    public const MODE_FRAGMENT = 'fragment';

    private static ?string $staticTitle = null;
    private static array $staticMeta = [];
    private static string $staticLang = 'zh-CN';

    private static array $staticInjections = [
        'head' => [],
        'body_start' => [],
        'body_end' => [],
    ];

    private string $title;
    private array $meta = [];
    private ?string $header = null;
    private ?string $main = null;
    private ?string $footer = null;
    private string $lang = 'zh-CN';
    private AssetRegistry $assets;
    private string $mode = self::MODE_FULL;

    private array $injections = [
        'head' => [],
        'body_start' => [],
        'body_end' => [],
    ];

    public function __construct(string $title = '')
    {
        $this->title = $title ?: (self::$staticTitle ?? '');
        $this->assets = AssetRegistry::getInstance();
        $this->lang = self::$staticLang;
        $this->meta = array_merge([
            ['charset', 'UTF-8'],
            ['viewport', 'width=device-width, initial-scale=1.0'],
        ], self::$staticMeta);

        // WASM 环境下自动切换为 partial 模式
        if (AppEnvironment::isWasm()) {
            $this->mode = self::MODE_PARTIAL;
        }
    }

    public static function make(string $title = ''): static
    {
        return new static($title);
    }

    /**
     * 设置渲染模式
     *
     * @param string $mode full（完整文档）| partial（仅内容）| fragment（片段）
     * @return static
     */
    public function mode(string $mode): static
    {
        if (!in_array($mode, [self::MODE_FULL, self::MODE_PARTIAL, self::MODE_FRAGMENT], true)) {
            throw new \InvalidArgumentException("Invalid mode: {$mode}");
        }
        $this->mode = $mode;
        return $this;
    }

    /**
     * 全局注册 JS
     */
    public static function registerScript(string $id, string $js): void
    {
        AssetRegistry::getInstance()->registerScript($id, $js);
    }

    public static function injectStatic(string $location, string $html): void
    {
        if (isset(self::$staticInjections[$location])) {
            self::$staticInjections[$location][] = $html;
        }
    }

    public static function addMeta(string $name, string $content): void
    {
        self::$staticMeta[] = [$name, $content];
    }

    public static function setTitle(string $title): void
    {
        self::$staticTitle = $title;
    }

    public static function setLang(string $lang): void
    {
        self::$staticLang = $lang;
    }

    public function inject(string $location, string $html): static
    {
        if (isset($this->injections[$location])) {
            $this->injections[$location][] = $html;
        }
        return $this;
    }

    /**
     * 标记加载脚本
     */
    public function requireScript(string ...$ids): static
    {
        foreach ($ids as $id) {
            $this->assets->requireScript($id);
        }
        return $this;
    }

    /**
     * 注册并加载脚本
     */
    public function script(string $id, string $js): static
    {
        $this->assets->registerScript($id, $js);
        $this->assets->requireScript($id);
        return $this;
    }

    /**
     * 实例级别添加 Meta
     */
    public function meta(string $name, string $content): static
    {
        $this->meta[] = [$name, $content];
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function lang(string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }

    public function ux(): static
    {
        $this->assets->ux();
        return $this;
    }

    public function css(string $href, ?string $id = null): static
    {
        $this->assets->css($href, $id);
        return $this;
    }

    public function js(string $src, bool $defer = true, ?string $id = null): static
    {
        $this->assets->js($src, $defer, $id);
        return $this;
    }

    public function header(mixed $content): static
    {
        $this->header = $this->resolveContent($content);
        return $this;
    }

    public function main(mixed $content): static
    {
        if (!$content instanceof Element && !$content instanceof \Framework\Component\Live\LiveComponent && !$content instanceof \Framework\UX\UXComponent) {
            throw new \InvalidArgumentException('Document::main() only accepts Element or LiveComponent.');
        }
        $this->main = $this->resolveContent($content);
        return $this;
    }

    public function footer(mixed $content): static
    {
        $this->footer = $this->resolveContent($content);
        return $this;
    }

    public function render(): string
    {
        // WASM 环境或 partial 模式：只输出内容
        if ($this->mode === self::MODE_PARTIAL || $this->mode === self::MODE_FRAGMENT) {
            return $this->renderPartial();
        }

        // 完整文档模式（默认 Web 环境）
        return $this->renderFull();
    }

    /**
     * 渲染完整 HTML 文档（Web 模式）
     */
    private function renderFull(): string
    {
        $this->assets->core();
        $session = new Session();
        $csrfToken = $session->token();

        $html = '<!DOCTYPE html>';
        $html .= '<html lang="' . htmlspecialchars($this->lang) . '">';

        $html .= '<head>';
        foreach ($this->meta as [$name, $content]) {
            if ($name === 'charset') {
                $html .= '<meta charset="' . htmlspecialchars($content) . '">';
            } elseif ($name === 'viewport') {
                $html .= '<meta name="viewport" content="' . htmlspecialchars($content) . '">';
            } else {
                $html .= '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">';
            }
        }
        $html .= '<title>' . htmlspecialchars($this->title) . '</title>';
        $html .= '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
        $html .= $this->assets->renderCss();

        foreach (self::$staticInjections['head'] as $injected) $html .= $injected;

        $html .= '</head>';

        $html .= '<body>';

        foreach (self::$staticInjections['body_start'] as $injected) $html .= $injected;

        if ($this->header) $html .= $this->header;
        if ($this->main) $html .= $this->main;
        if ($this->footer) $html .= $this->footer;

        foreach (self::$staticInjections['body_end'] as $injected) $html .= $injected;

        $html .= $this->assets->renderJs();
        $html .= '</body></html>';

        return $html;
    }

    /**
     * 渲染内容片段（WASM/Tauri 模式）
     *
     * 只输出 <main> 内容，不包含 html/head/body 外壳。
     * 适用于 Tauri 等 WebView 已有外壳的场景。
     */
    private function renderPartial(): string
    {
        $html = '';

        // fragment 模式：只输出 main
        if ($this->mode === self::MODE_FRAGMENT) {
            if ($this->main) {
                $html .= $this->main;
            }
            return $html;
        }

        // partial 模式：输出 header + main + footer（无 html/head/body）
        if ($this->header) {
            $html .= $this->header;
        }

        if ($this->main) {
            $html .= $this->main;
        }

        if ($this->footer) {
            $html .= $this->footer;
        }

        return $html;
    }

    /**
     * 输出 JSON 格式（用于 Tauri JS Bridge 调用）
     *
     * 返回结构化数据，方便前端处理：
     * ```json
     * {
     *   "title": "页面标题",
     *   "html": "<main>...</main>",
     *   "mode": "partial",
     *   "assets": { "css": [...], "js": [...] }
     * }
     * ```
     *
     * Tauri 前端调用示例：
     * ```typescript
     * const result = await invoke('render_page', { path: '/dashboard' });
     * document.getElementById('app-content').innerHTML = result.html;
     * document.title = result.title;
     * ```
     */
    public function toJson(): string
    {
        return json_encode([
            'title' => $this->title,
            'html' => $this->render(),
            'mode' => $this->mode,
            'assets' => [
                'css' => $this->assets->getCssList(),
                'js' => $this->assets->getJsList(),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function resolveContent(mixed $content): string
    {
        if ($content instanceof Element) return $content->render();
        if ($content instanceof \Framework\Component\Live\LiveComponent) return $content->toHtml();
        return (string)$content;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
