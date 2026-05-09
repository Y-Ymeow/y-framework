<?php

declare(strict_types=1);

namespace Framework\View\Document;

class AssetRegistry
{
    private static ?self $instance = null;
    private array $cssFiles = [];
    private array $jsFiles = [];
    private array $namedScripts = [];
    private array $requestedScripts = [];
    private array $loaded = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function registerScript(string $id, string $js): self
    {
        $this->namedScripts[$id] = $js;
        $this->requireScript($id);

        if (function_exists('\\cache')) {
            \cache()->set('js_resource:' . $id, $js, 3600);
        }
        return $this;
    }

    public function requireScript(string $id): self
    {
        $this->requestedScripts[$id] = true;
        return $this;
    }

    public function getScriptContent(string $id): ?string
    {
        return $this->namedScripts[$id] ?? null;
    }

    public function getRequestedScriptIds(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->requestedScripts),
            array_keys($this->namedScripts)
        )));
    }

    public function buildScriptUrl(array $ids): string
    {
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => is_string($id) && $id !== '')));

        if (empty($ids)) {
            return '';
        }

        $idsParam = implode(',', $ids);
        $v = substr(md5($idsParam), 0, 8);

        return '/_js?ids=' . urlencode($idsParam) . '&v=' . $v;
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

    public function inlineStyle(string $id, string $css): self
    {
        CssCollector::getInstance()->add($id, $css);
        return $this;
    }

    public function addCssSnippet(string $id, string $css): self
    {
        CssCollector::getInstance()->add($id, $css);
        return $this;
    }

    public function core(): self
    {
        $this->css('/_css', 'generated-css');
        return $this;
    }

    public function ui(): self
    {
        if (\Framework\Support\Asset::isDev()) {
            $this->js('http://localhost:5173/@vite/client', true, 'vite-client', true);
        }

        $this->js(dist('./resources/js/ui.js'), true, 'ui-js', true);
        foreach (dist_css('./resources/js/ui.js') as $index => $cssUrl) {
            $this->css($cssUrl, 'dist-ui-css-' . $index);
        }
        return $this;
    }

    public function ux(): self
    {
        $this->js(dist('resources/js/ux.js'), true, 'ux-js', true);
        foreach (dist_css('resources/js/ux.js') as $index => $cssUrl) {
            $this->css($cssUrl, 'dist-ux-css-' . $index);
        }
        return $this;
    }

    public function renderCss(): string
    {
        $html = '';
        $snippetIds = CssCollector::getInstance()->getSnippetIds();

        foreach ($this->cssFiles as $css) {
            $id = $css['id'] ? ' id="' . htmlspecialchars($css['id']) . '"' : '';
            $href = $css['href'];

            if ($css['id'] === 'generated-css' && !empty($snippetIds)) {
                $ids = implode(',', $snippetIds);
                $v = substr(md5($ids), 0, 8);
                $sep = str_contains($href, '?') ? '&' : '?';
                $href .= $sep . 'snippets=' . urlencode($ids) . '&v=' . $v;
            }

            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($href) . '"' . $id . '>';
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

        $idsToLoad = $this->getRequestedScriptIds();

        if (!empty($idsToLoad)) {
            $html .= '<script src="' . htmlspecialchars($this->buildScriptUrl($idsToLoad)) . '" defer></script>';
        }

        return $html;
    }

    public function getCssList(): array
    {
        return $this->cssFiles;
    }

    public function getJsList(): array
    {
        return $this->jsFiles;
    }

    public function getLoadedAssets(): array
    {
        return array_keys($this->loaded);
    }

    public function isLoaded(string $key): bool
    {
        return isset($this->loaded[$key]);
    }
}
