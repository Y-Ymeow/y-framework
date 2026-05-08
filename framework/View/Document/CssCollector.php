<?php

declare(strict_types=1);

namespace Framework\View\Document;

class CssCollector
{
    private static ?self $instance = null;
    private array $snippets = [];
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

    public function add(string $id, string $css): self
    {
        if (isset($this->loaded[$id])) return $this;

        $this->snippets[$id] = $css;
        $this->loaded[$id] = true;

        if (function_exists('\\cache')) {
            \cache()->set('css_snippet:' . $id, $css, 3600);
            $ids = \cache()->get('css_snippet_ids') ?: [];
            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
                \cache()->set('css_snippet_ids', $ids, 3600);
            }
        }

        return $this;
    }

    public function has(string $id): bool
    {
        return isset($this->loaded[$id]);
    }

    public function getSnippet(string $id): ?string
    {
        if (isset($this->snippets[$id])) return $this->snippets[$id];

        if (function_exists('\\cache')) {
            $cached = \cache()->get('css_snippet:' . $id);
            if ($cached) {
                $this->snippets[$id] = $cached;
                $this->loaded[$id] = true;
                return $cached;
            }
        }

        return null;
    }

    public function getSnippets(): array
    {
        return $this->snippets;
    }

    public function getSnippetIds(): array
    {
        return array_keys($this->loaded);
    }

    public function loadFromCache(string $key = ''): self
    {
        if (!function_exists('\\cache')) return $this;

        $ids = \cache()->get('css_snippet_ids:' . $key);
        if (!is_array($ids)) return $this;

        foreach ($ids as $id) {
            if (isset($this->loaded[$id])) continue;
            $cached = \cache()->get('css_snippet:' . $id);
            if ($cached) {
                $this->snippets[$id] = $cached;
                $this->loaded[$id] = true;
            }
        }

        return $this;
    }

    public function collect(): string
    {
        if (empty($this->snippets)) return '';

        $css = '';
        foreach ($this->snippets as $id => $snippet) {
            $css .= "/* --- {$id} --- */\n";
            $css .= $snippet . "\n\n";
        }
        return $css;
    }

    public function renderTag(): string
    {
        $ids = $this->getSnippetIds();
        if (empty($ids)) return '';

        $idsStr = implode(',', $ids);
        $v = substr(md5($idsStr), 0, 8);
        return '<link rel="stylesheet" href="/_css?snippets=' . urlencode($idsStr) . '&v=' . $v . '">';
    }
}
