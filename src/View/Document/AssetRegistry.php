<?php

declare(strict_types=1);

namespace Framework\View\Document;

class AssetRegistry
{
    private static ?AssetRegistry $instance = null;
    private array $cssFiles = [];
    private array $jsFiles = [];
    private array $inlineStyles = [];
    private array $namedScripts = []; // [id => code]
    private array $requestedScripts = []; // [id => true]
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

    /**
     * 注册命名的 JS 代码块（持久化到缓存）
     */
    public function registerScript(string $id, string $js): self
    {
        $this->namedScripts[$id] = $js;
        
        // 自动将注册的脚本标记为“待加载”，这样就不需要手动调用 requireScript 了
        $this->requireScript($id);

        if (function_exists('\\cache')) {
            \cache()->set('js_resource:' . $id, $js, 3600);
        }
        return $this;
    }

    /**
     * 请求加载特定的脚本 ID
     */
    public function requireScript(string $id): self
    {
        $this->requestedScripts[$id] = true;
        return $this;
    }

    public function getScriptContent(string $id): ?string
    {
        return $this->namedScripts[$id] ?? null;
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

    public function inlineStyle(string $style): self
    {
        $this->inlineStyles[] = $style;
        return $this;
    }

    /**
     * 核心资源注册
     */
    public function core(): self
    {
        // 显式注册 CSS 路由
        $this->css('/_css', 'generated-css');
        
        // 我们不需要在这里显式 add js，因为 renderJs 会自动根据收集到的 ID 生成链接
        return $this;
    }

    public function ui(): self
    {
        if (\Framework\Support\Asset::isDev()) {
            $this->js('http://localhost:5173/@vite/client', true, 'vite-client', true);
        }
        $this->js(dist('ui.js'), true, 'ui-js', true);
        foreach (dist_css('ui.js') as $index => $cssUrl) {
            $this->css($cssUrl, 'dist-ui-css-' . $index);
        }
        return $this;
    }

    public function ux(): self
    {
        $this->js(dist('ux.js'), true, 'ux-js', true);
        foreach (dist_css('ux.js') as $index => $cssUrl) {
            $this->css($cssUrl, 'dist-ux-css-' . $index);
        }
        return $this;
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
            $html .= '<script src="' . htmlspecialchars($js['src']) . '"' . $defer . $module . $id . '></script>';
        }

        // 核心改动：如果 requestedScripts 为空，但 namedScripts 有内容（比如 DebugBar 刚刚注册的）
        // 或者显式合并所有 ID
        $idsToLoad = array_unique(array_merge(
            array_keys($this->requestedScripts),
            array_keys($this->namedScripts)
        ));

        if (!empty($idsToLoad)) {
            $ids = implode(',', $idsToLoad);
            $v = substr(md5($ids), 0, 8);
            $html .= '<script src="/_js?ids=' . urlencode($ids) . '&v=' . $v . '" defer></script>';
        }

        return $html;
    }

    /**
     * 获取已注册的 CSS 文件列表（用于 WASM JSON 输出）
     *
     * @return array<int, array{href: string, id: string|null}>
     */
    public function getCssList(): array
    {
        return $this->cssFiles;
    }

    /**
     * 获取已注册的 JS 文件列表（用于 WASM JSON 输出）
     *
     * @return array<int, array{src: string, defer: bool, id: string|null, module: bool}>
     */
    public function getJsList(): array
    {
        return $this->jsFiles;
    }
}
