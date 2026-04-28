<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\UX\RichEditor\RichEditorExtension;
use Framework\UX\RichEditor\ExtensionRegistry;
use Framework\CSS\RichEditorRules;

class RichEditor extends FormField
{
    protected int $rows = 10;
    protected array $toolbar = ['bold', 'italic', 'underline', 'strike', '|', 'heading', 'quote', 'code', '|', 'list', 'link', 'image'];
    protected bool $minimal = false;
    protected bool $border = true;
    protected ?string $width = null;
    protected ?string $height = null;
    protected string $placeholder = '';
    protected array $extensions = [];
    protected array $parsers = [];
    protected string $outputFormat = 'html';
    protected ?string $liveModel = null;
    protected ?string $liveAction = null;
    protected ?string $liveEvent = 'change';

    public function __construct()
    {
        parent::__construct();
        AssetRegistry::getInstance()->inlineStyle(RichEditorRules::getStyles());
    }

    public function liveModel(string $name): static
    {
        $this->liveModel = $name;
        return $this;
    }

    public function liveAction(string $action, string $event = 'change'): static
    {
        $this->liveAction = $action;
        $this->liveEvent = $event;
        return $this;
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    public function toolbar(array $items): static
    {
        $this->toolbar = $items;
        return $this;
    }

    public function minimal(bool $minimal = true): static
    {
        $this->minimal = $minimal;
        if ($minimal) {
            $this->border = false;
        }
        return $this;
    }

    public function border(bool $border = true): static
    {
        $this->border = $border;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function outputFormat(string $format): static
    {
        $this->outputFormat = in_array($format, ['html', 'markdown', 'text']) ? $format : 'html';
        return $this;
    }

    public function extension(string $name, RichEditorExtension $extension): static
    {
        $this->extensions[$name] = $extension;
        return $this;
    }

    public function parser(callable $parser, string $name = 'default'): static
    {
        $this->parsers[$name] = $parser;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        if ($this->width) {
            $groupEl->style("width: {$this->width}");
        }

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $editorId = $this->name . '-editor';
        $inputId = $this->name;

        $wrapperClass = 'ux-rich-editor';
        if ($this->minimal) $wrapperClass .= ' ux-rich-editor--minimal';
        if ($this->border) $wrapperClass .= ' ux-rich-editor--border';

        $wrapperEl = Element::make('div')
            ->class($wrapperClass)
            ->data('editor-id', $editorId);

        if ($this->height) {
            $wrapperEl->style("height: {$this->height}");
        }

        if (!$this->minimal) {
            $toolbarEl = $this->buildToolbar($editorId);
            $wrapperEl->child($toolbarEl);
        }

        $editorAreaEl = Element::make('div')
            ->class('ux-rich-editor__area')
            ->attr('contenteditable', 'true')
            ->attr('id', $editorId)
            ->data('input-id', $inputId);

        if ($this->placeholder) {
            $editorAreaEl->attr('data-placeholder', $this->placeholder);
        }

        $content = $this->value ?? '';
        if ($content) {
            $editorAreaEl->html((string)$content);
        }

        if ($this->liveModel) {
            $editorAreaEl->data('model', $this->liveModel);
        }

        $wrapperEl->child($editorAreaEl);

        $hiddenInput = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('id', $inputId)
            ->attr('name', $this->name);

        if ($this->value !== null) {
            $hiddenInput->attr('value', (string)$this->value);
        }

        if ($this->liveModel) {
            $hiddenInput->attr('data-model', $this->liveModel);
            $hiddenInput->liveModel($this->liveModel);
        }

        if ($this->liveAction) {
            $hiddenInput->attr('data-action', $this->liveAction);
            $hiddenInput->attr('data-action-event', $this->liveEvent);
            $hiddenInput->data('action-params', json_encode(['name' => $this->name, 'content' => '__value__']));
        }

        foreach ($this->buildFieldAttrs() as $key => $value) {
            if (!in_array($key, ['id', 'name'])) {
                $hiddenInput->attr($key, $value);
            }
        }

        $wrapperEl->child($hiddenInput);
        $groupEl->child($wrapperEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }

    protected function buildToolbar(string $editorId): Element
    {
        $toolbarEl = Element::make('div')->class('ux-rich-editor__toolbar');

        foreach ($this->toolbar as $item) {
            if ($item === '|') {
                $toolbarEl->child(Element::make('span')->class('ux-rich-editor__separator'));
                continue;
            }

            $buttonConfig = $this->getToolbarButtonConfig($item);
            if ($buttonConfig) {
                $btn = Element::make('button')
                    ->class('ux-rich-editor__btn')
                    ->attr('type', 'button')
                    ->data('action', $item)
                    ->data('editor', $editorId)
                    ->attr('title', $buttonConfig['title']);

                if (!empty($buttonConfig['icon'])) {
                    $btn->html($buttonConfig['icon']);
                } else {
                    $btn->text($buttonConfig['label'] ?? $item);
                }

                $toolbarEl->child($btn);
            }
        }

        foreach ($this->extensions as $name => $extension) {
            $extBtn = $extension->getToolbarButton($editorId);
            if ($extBtn) {
                $toolbarEl->child($extBtn);
            }
        }

        return $toolbarEl;
    }

    protected function getToolbarButtonConfig(string $action): ?array
    {
        $configs = [
            'bold' => ['title' => '粗体', 'label' => 'B', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6V4zm0 8h9a4 4 0 014 4 4 4 0 01-4 4H6v-8z"/></svg>'],
            'italic' => ['title' => '斜体', 'label' => 'I', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10 4v3h2.21l-3.42 10H6v3h8v-3h-2.21l3.42-10H18V4h-8z"/></svg>'],
            'underline' => ['title' => '下划线', 'label' => 'U', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>'],
            'strike' => ['title' => '删除线', 'label' => 'S', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M17.75 9L14 4.5l-6 7.5H6l-2 3h4.5l-3 4h12.5l3-4H13l5.5-6.5-1.75-2z"/></svg>'],
            'heading' => ['title' => '标题', 'label' => 'H', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M5 4v12h2V9h4v7h2V4h-2v5H7V4H5zm13 4h-2v2h-2v2h2v6h2v-6h2V8h-2V6z"/></svg>'],
            'quote' => ['title' => '引用', 'label' => '"', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>'],
            'code' => ['title' => '代码', 'label' => '</>', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>'],
            'list' => ['title' => '列表', 'label' => '•', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>'],
            'link' => ['title' => '链接', 'label' => '🔗', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'],
            'image' => ['title' => '图片', 'label' => '📷', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>'],
            'undo' => ['title' => '撤销', 'label' => '↩', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>'],
            'redo' => ['title' => '重做', 'label' => '↪', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>'],
            'clear' => ['title' => '清除格式', 'label' => '✕', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 3v18h18V3H3zm16 16H5V5h14v14zM11 7h2v6h-2V7zm0 8h2v2h-2v-2z"/></svg>'],
        ];

        return $configs[$action] ?? null;
    }

    public function parseContent(string $content, string $parserName = 'default'): string
    {
        if (isset($this->parsers[$parserName])) {
            return ($this->parsers[$parserName])($content);
        }

        foreach ($this->extensions as $extension) {
            $content = $extension->parse($content);
        }

        return $content;
    }

    public function sanitize(string $content): string
    {
        $allowedTags = '<p><br><strong><b><em><i><u><s><strike><del><h1><h2><h3><h4><h5><h6><blockquote><pre><code><ul><ol><li><a><img><div><span>';
        $allowedAttrs = ['href', 'src', 'alt', 'title', 'class', 'id', 'target'];

        $content = strip_tags($content, $allowedTags);

        $content = preg_replace_callback('/<([a-z][a-z0-9]*)[^>]*?\s+(?:on|javascript:)[^>]*>/i', function ($m) {
            return '<' . $m[1] . '>';
        }, $content);

        return $content;
    }
}
