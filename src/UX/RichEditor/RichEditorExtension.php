<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

abstract class RichEditorExtension
{
    protected string $name = '';
    protected string $icon = '';
    protected string $label = '';
    protected string $title = '';
    protected array $config = [];

    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initialize();
    }

    abstract public function getName(): string;

    protected function getDefaultConfig(): array
    {
        return [];
    }

    protected function initialize(): void
    {
    }

    public function getToolbarButton(string $editorId): ?Element
    {
        if (empty($this->icon) && empty($this->label)) {
            return null;
        }

        $btn = Element::make('button')
            ->class('ux-rich-editor__btn ux-rich-editor__btn--extension')
            ->attr('type', 'button')
            ->data('extension', $this->name)
            ->data('editor', $editorId)
            ->attr('title', $this->title ?: $this->name);

        if ($this->icon) {
            $btn->html($this->icon);
        } else {
            $btn->text($this->label);
        }

        return $btn;
    }

    abstract public function execute(string $content, array $params = []): string;

    public function parse(string $content): string
    {
        return $content;
    }

    public function renderPreview(string $content): string
    {
        return $content;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function setConfig(string $key, mixed $value): static
    {
        $this->config[$key] = $value;
        return $this;
    }
}
