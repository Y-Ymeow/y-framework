<?php

declare(strict_types=1);

namespace Framework\View\Document;

class AssetRegistry
{
    private static ?AssetRegistry $instance = null;
    private array $cssFiles = [];
    private array $jsFiles = [];
    private array $inlineStyles = [];
    private array $inlineScripts = [];
    private array $loaded = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function css(string $href, ?string $id = null): self
    {
        $key = $id ?? $href;
        if (!isset($this->loaded[$key])) {
            $this->cssFiles[] = ['href' => $href, 'id' => $id];
            $this->loaded[$key] = true;
        }
        return $this;
    }

    public function js(string $src, bool $defer = true, ?string $id = null, bool $isModule = false): self
    {
        $key = $id ?? $src;
        if (!isset($this->loaded[$key])) {
            $this->jsFiles[] = ['src' => $src, 'defer' => $defer, 'id' => $id, 'module' => $isModule];
            $this->loaded[$key] = true;
        }
        return $this;
    }

    public function inlineStyle(string $css): self
    {
        $hash = md5($css);
        if (!isset($this->loaded['style:' . $hash])) {
            $this->inlineStyles[] = $css;
            $this->loaded['style:' . $hash] = true;
        }
        return $this;
    }

    public function inlineScript(string $js): self
    {
        $hash = md5($js);
        if (!isset($this->loaded['script:' . $hash])) {
            $this->inlineScripts[] = $js;
            $this->loaded['script:' . $hash] = true;
        }
        return $this;
    }

    public function core(): self
    {
        $this->css('/_css', 'generated-css');
        // y-ui 的集成由项目自身通过 js() 加载 resources/js/ux.js 处理，
        // 但我们在这里预留一个钩子或者核心 CSS
        return $this;
    }

    public function ui(): self
    {
        if (\Framework\Support\Asset::isDev()) {
            $this->js('http://localhost:5173/@vite/client', true, 'vite-client', true);
        }
        $this->js(vite('resources/js/ui.js'), true, 'ui-js', true);
        $cssIndex = 0;
        foreach (vite_css('resources/js/ui.js') as $cssUrl) {
            $this->css($cssUrl, 'vite-ui-app-css-' . $cssIndex);
            $cssIndex++;
        }
        return $this;
    }

    public function ux(): self
    {
        $this->js(vite('resources/js/ux.js'), true, 'ux-js', true);
        $cssIndex = 0;
        foreach (vite_css('resources/js/ux.js') as $cssUrl) {
            $this->css($cssUrl, 'vite-ux-app-css-' . $cssIndex);
            $cssIndex++;
        }
        return $this;
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    public function getInlineStyles(): array
    {
        return $this->inlineStyles;
    }

    public function getInlineScripts(): array
    {
        return $this->inlineScripts;
    }

    public function isLoaded(string $key): bool
    {
        return isset($this->loaded[$key]);
    }

    public function renderCss(): string
    {
        $html = '';
        foreach ($this->cssFiles as $css) {
            $id = $css['id'] ? ' id="' . htmlspecialchars($css['id']) . '"' : '';
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($css['href']) . '"' . $id . '>';
        }
        if (!empty($this->inlineStyles)) {
            $html .= '<style>' . implode("\n", $this->inlineStyles) . '</style>';
        }
        return $html;
    }

    public function renderJs(): string
    {
        $html = '';
        foreach ($this->jsFiles as $js) {
            $id = $js['id'] ? ' id="' . htmlspecialchars($js['id']) . '"' : '';
            $defer = $js['defer'] ? ' defer' : '';
            $module = $js['module'] ? ' type="module"' : '';
            $attrs = '';
            if (!empty($js['attrs'])) {
                foreach ($js['attrs'] as $name => $value) {
                    $attrs .= ' ' . htmlspecialchars($name) . '="' . htmlspecialchars((string)$value) . '"';
                }
            }
            $html .= '<script src="' . htmlspecialchars($js['src']) . '"' . $defer . $module . $id . $attrs . '></script>';
        }
        if (!empty($this->inlineScripts)) {
            $html .= '<script>' . implode("\n", $this->inlineScripts) . '</script>';
        }
        return $html;
    }
}
