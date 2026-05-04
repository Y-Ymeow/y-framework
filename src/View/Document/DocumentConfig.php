<?php

declare(strict_types=1);

namespace Framework\View\Document;

class DocumentConfig
{
    private static ?self $instance = null;

    private string $title = '';
    private string $lang = 'zh-CN';
    private array $meta = [];
    private array $injections = [
        'head' => [],
        'body_start' => [],
        'body_end' => [],
    ];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->meta[] = ['charset', 'UTF-8'];
            self::$instance->meta[] = ['viewport', 'width=device-width, initial-scale=1.0'];
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function lang(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function addMeta(string $name, string $content): self
    {
        $this->meta[] = [$name, $content];
        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function inject(string $location, string $html): self
    {
        if (isset($this->injections[$location])) {
            $this->injections[$location][] = $html;
        }
        return $this;
    }

    public function getInjections(string $location): array
    {
        return $this->injections[$location] ?? [];
    }
}
