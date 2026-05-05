<?php

declare(strict_types=1);

namespace Framework\View\Document;

use Framework\Foundation\AppEnvironment;
use Framework\View\Base\Element;

class Document
{
    public const MODE_FULL = 'full';
    public const MODE_PARTIAL = 'partial';
    public const MODE_FRAGMENT = 'fragment';

    private string $title;
    private array $meta = [];
    private ?string $header = null;
    private ?string $main = null;
    private ?string $footer = null;
    private string $lang;
    private AssetRegistry $assets;
    private string $mode = self::MODE_FULL;

    private array $injections = [
        'head' => [],
        'body_start' => [],
        'body_end' => [],
    ];

    public function __construct(string $title = '')
    {
        $config = DocumentConfig::getInstance();
        $this->title = $title ?: $config->getTitle();
        $this->lang = $config->getLang();
        $this->meta = $config->getMeta();
        $this->assets = AssetRegistry::getInstance();

        if (AppEnvironment::isWasm()) {
            $this->mode = self::MODE_PARTIAL;
        }
    }

    public static function make(string $title = ''): static
    {
        return new static($title);
    }

    public function mode(string $mode): static
    {
        if (!in_array($mode, [self::MODE_FULL, self::MODE_PARTIAL, self::MODE_FRAGMENT], true)) {
            throw new \InvalidArgumentException("Invalid mode: {$mode}");
        }
        $this->mode = $mode;
        return $this;
    }

    public static function registerScript(string $id, string $js): void
    {
        AssetRegistry::getInstance()->registerScript($id, $js);
    }

    public static function injectStatic(string $location, string $html): void
    {
        DocumentConfig::getInstance()->inject($location, $html);
    }

    public static function addMeta(string $name, string $content): void
    {
        DocumentConfig::getInstance()->addMeta($name, $content);
    }

    public static function setTitle(string $title): void
    {
        DocumentConfig::getInstance()->title($title);
    }

    public static function setLang(string $lang): void
    {
        DocumentConfig::getInstance()->lang($lang);
    }

    public static function sseConfig(array $channels = [])
    {
        $metaElement = \Framework\Component\Live\Sse\SseHelper::metaElement($channels);
        $rendered = $metaElement->render();
        self::injectStatic('head', $rendered);
    }

    public function inject(string $location, string $html): static
    {
        if (isset($this->injections[$location])) {
            $this->injections[$location][] = $html;
        }
        return $this;
    }

    public function requireScript(string ...$ids): static
    {
        foreach ($ids as $id) {
            $this->assets->requireScript($id);
        }
        return $this;
    }

    public function script(string $id, string $js): static
    {
        $this->assets->registerScript($id, $js);
        $this->assets->requireScript($id);
        return $this;
    }

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
        if ($this->mode === self::MODE_PARTIAL || $this->mode === self::MODE_FRAGMENT) {
            return $this->renderPartial();
        }

        return $this->renderFull();
    }

    private function renderFull(): string
    {
        $config = DocumentConfig::getInstance();
        $this->assets->core();
        $session = session();
        $csrfToken = $session->token();

        $html = '<!DOCTYPE html>';
        $html .= '<html lang="' . htmlspecialchars($this->lang ?: $config->getLang()) . '">';

        $html .= '<head>';
        foreach (array_merge($config->getMeta(), $this->meta) as [$name, $content]) {
            if ($name === 'charset') {
                $html .= '<meta charset="' . htmlspecialchars($content) . '">';
            } elseif ($name === 'viewport') {
                $html .= '<meta name="viewport" content="' . htmlspecialchars($content) . '">';
            } else {
                $html .= '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">';
            }
        }
        $html .= '<title>' . htmlspecialchars($this->title ?: $config->getTitle()) . '</title>';
        $html .= '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
        $html .= $this->assets->renderCss();

        foreach ($config->getInjections('head') as $injected) $html .= $injected;
        foreach ($this->injections['head'] as $injected) $html .= $injected;

        $html .= '</head>';

        $html .= '<body>';

        foreach ($config->getInjections('body_start') as $injected) $html .= $injected;
        foreach ($this->injections['body_start'] as $injected) $html .= $injected;

        if ($this->header) $html .= $this->header;
        if ($this->main) $html .= $this->main;
        if ($this->footer) $html .= $this->footer;

        foreach ($config->getInjections('body_end') as $injected) $html .= $injected;
        foreach ($this->injections['body_end'] as $injected) $html .= $injected;

        $html .= $this->assets->renderJs();
        $html .= '</body></html>';

        return $html;
    }

    private function renderPartial(): string
    {
        $html = '';

        if ($this->mode === self::MODE_FRAGMENT) {
            if ($this->main) {
                $html .= $this->main;
            }
            return $html;
        }

        if ($this->header) $html .= $this->header;
        if ($this->main) $html .= $this->main;
        if ($this->footer) $html .= $this->footer;

        return $html;
    }

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
