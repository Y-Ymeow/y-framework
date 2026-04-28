<?php

declare(strict_types=1);

namespace Framework\View\Document;

use Framework\Http\Session;
use Framework\View\Base\Element;

class Document
{
    private static ?string $staticTitle = null;
    private static array $staticMeta = [];
    private static string $staticLang = 'zh-CN';

    // 静态注入容器
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

    // 实例注入容器
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
    }

    public static function make(string $title = ''): static
    {
        return new static($title);
    }

    /**
     * 全局注入 HTML 代码
     */
    public static function injectStatic(string $location, string $html): void
    {
        if (isset(self::$staticInjections[$location])) {
            // Security: Strip dangerous HTML tags and event handlers
            $html = self::sanitizeHtml($html);
            self::$staticInjections[$location][] = $html;
        }
    }

    // 兼容旧方法
    public static function injectBeforeBody(string $html): void
    {
        self::injectStatic('body_start', $html);
    }
    public static function injectAfterBody(string $html): void
    {
        self::injectStatic('body_end', $html);
    }

    public static function setTitle(string $title): void
    {
        self::$staticTitle = $title;
    }
    public static function setLang(string $lang): void
    {
        self::$staticLang = $lang;
    }
    public static function addMeta(string $name, string $content): void
    {
        self::$staticMeta[] = [$name, $content];
    }

    /**
     * 实例级别注入 HTML 代码
     */
    public function inject(string $location, string $html): static
    {
        if (isset($this->injections[$location])) {
            // Security: Strip dangerous HTML tags and event handlers
            $html = self::sanitizeHtml($html);
            $this->injections[$location][] = $html;
        }
        return $this;
    }

    public function injectHead(string $html): static
    {
        return $this->inject('head', $html);
    }
    public function injectBodyStart(string $html): static
    {
        return $this->inject('body_start', $html);
    }
    public function injectBodyEnd(string $html): static
    {
        return $this->inject('body_end', $html);
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
        if (!$content instanceof Element && !$content instanceof \Framework\Component\LiveComponent && !$content instanceof \Framework\UX\UXComponent) {
            throw new \InvalidArgumentException('Document::main() only accepts Element or LiveComponent objects for security reasons. Direct HTML strings are not allowed.');
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
        $this->assets->core();
        $session = new Session();
        $csrfToken = $session->token();

        $html = '<!DOCTYPE html>';
        $html .= '<html lang="' . htmlspecialchars($this->lang) . '">';

        // --- Head ---
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

        // 注入 Head 代码
        foreach (self::$staticInjections['head'] as $injected) $html .= $injected;
        foreach ($this->injections['head'] as $injected) $html .= $injected;

        $html .= '</head>';

        // --- Body ---
        $html .= '<body>';

        // 注入 Body Start 代码
        foreach (self::$staticInjections['body_start'] as $injected) $html .= $injected;
        foreach ($this->injections['body_start'] as $injected) $html .= $injected;

        if ($this->header) $html .= $this->header;
        if ($this->main) $html .= $this->main;
        if ($this->footer) $html .= $this->footer;

        // 注入 Body End 代码
        foreach (self::$staticInjections['body_end'] as $injected) $html .= $injected;
        foreach ($this->injections['body_end'] as $injected) $html .= $injected;

        $html .= $this->assets->renderJs();
        $html .= '</body></html>';

        return $html;
    }

    private function resolveContent(mixed $content): string
    {
        if ($content instanceof Element) return $content->render();
        if ($content instanceof \Framework\Component\LiveComponent) return $content->toHtml();
        if (is_string($content)) return $content;
        return (string)$content;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * 净化 HTML，移除可能造成 XSS 的标签和事件处理器
     */
    private static function sanitizeHtml(string $html): string
    {
        // 移除所有事件处理器属性 (on*)
        $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

        // 移除 script, style, iframe, object, embed 等危险标签
        $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'meta', 'link', 'base'];
        foreach ($dangerousTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $html);
            $html = preg_replace('/<' . $tag . '\b[^>]*\/?>/i', '', $html);
        }

        // 移除 javascript: data: vbscript: 等危险协议
        $html = preg_replace('/href\s*=\s*"(?:javascript|data|vbscript):/i', 'href="#"', $html);
        $html = preg_replace("/href\s*=\s*'(?:javascript|data|vbscript):/i", "href='#'", $html);
        $html = preg_replace('/src\s*=\s*"(?:javascript|data|vbscript):/i', 'src="#"', $html);
        $html = preg_replace("/src\s*=\s*'(?:javascript|data|vbscript):/i", "src='#'", $html);

        return $html;
    }
}
