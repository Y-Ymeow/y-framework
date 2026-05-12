<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\UX\UXLiveComponent;
use Framework\View\Base\Element;
use Framework\CSS\RichEditorRules;
use Framework\View\Document\AssetRegistry;

/**
 * RichEditor
 * 
 * 一个真正的单区域流式富文本编辑器。
 * 以 HTML 字符串为核心状态，提供流畅、连贯的文档编辑体验。
 */
class RichEditor extends UXLiveComponent
{
    #[State]
    public string $value = '';

    private string $fieldName = '';
    private string|array $fieldLabel = '';
    private string $fieldPlaceholder = '';
    private string $minHeight = '400px';
    private bool $fieldRequired = false;
    private string $fieldHelp = '';

    public function __construct(string $name = '')
    {
        parent::__construct();
        $this->fieldName = $name;
        AssetRegistry::getInstance()->inlineStyle('ux:rich-editor', RichEditorRules::getStyles());
    }

    public static function make(array|string $props = [], array $routeParams = []): static
    {
        if (is_string($props)) {
            $instance = new static($props);
            $instance->_invoke($routeParams);
            return $instance;
        }
        return parent::make($props, $routeParams);
    }

    public function name(string $name): static { $this->fieldName = $name; return $this; }
    public function label(string|array $label): static { $this->fieldLabel = $label; return $this; }
    public function placeholder(string $placeholder): static { $this->fieldPlaceholder = $placeholder; return $this; }
    public function minHeight(string $height): static { $this->minHeight = $height; return $this; }
    public function height(string $height): static { return $this->minHeight($height); }
    public function help(string $help): static { $this->fieldHelp = $help; return $this; }
    public function required(bool $required = true): static { $this->fieldRequired = $required; return $this; }

    public function value(mixed $value): static
    {
        if (is_string($value)) {
            $this->value = $value;
        }
        return $this;
    }

    #[LiveAction]
    public function sync(array $params): void
    {
        $this->value = $params['html'] ?? '';
    }

    public function render(): Element
    {
        $root = Element::make('div')->class('ux-rich-editor');

        if ($this->fieldLabel) {
            $label = Element::make('label')->class('ux-form-label')->text((string)$this->fieldLabel);
            if ($this->fieldRequired) {
                $label->child(Element::make('span')->class('text-danger')->text('*'));
            }
            $root->child($label);
        }

        $root->child($this->renderToolbar());

        // 核心：单区域 contenteditable 画布
        $canvas = Element::make('div')
            ->class('ux-rich-editor__area')
            ->attr('contenteditable', 'true')
            ->attr('data-placeholder', $this->fieldPlaceholder)
            ->style('min-height', $this->minHeight)
            // 失焦或输入时同步回 PHP
            ->attr('onblur', "this.dispatchEvent(new CustomEvent('sync-html', { detail: { html: this.innerHTML } }))")
            ->liveAction('sync', 'sync-html', ['html' => '$event.detail.html'])
            ->html($this->value ?: '<p><br></p>');

        $root->child($canvas);

        if ($this->fieldHelp) {
            $root->child(Element::make('div')->class('ux-form-help')->text($this->fieldHelp));
        }

        // 隐藏域用于传统的表单提交
        $root->child(
            Element::make('input')
                ->attr('type', 'hidden')
                ->attr('name', $this->fieldName)
                ->attr('value', $this->value)
        );

        return $root;
    }

    protected function renderToolbar(): Element
    {
        $toolbar = Element::make('div')->class('ux-rich-editor__toolbar');
        
        // 文本格式
        $toolbar->child($this->createBtn('Bold', 'bi bi-type-bold', 'bold'));
        $toolbar->child($this->createBtn('Italic', 'bi bi-type-italic', 'italic'));
        $toolbar->child($this->createBtn('Underline', 'bi bi-type-underline', 'underline'));
        
        $toolbar->child(Element::make('span')->class('ux-rich-editor__separator'));
        
        // 段落格式
        $toolbar->child($this->createBtn('H1', 'bi bi-type-h1', 'formatBlock', 'h1'));
        $toolbar->child($this->createBtn('H2', 'bi bi-type-h2', 'formatBlock', 'h2'));
        $toolbar->child($this->createBtn('Paragraph', 'bi bi-paragraph', 'formatBlock', 'p'));
        
        $toolbar->child(Element::make('span')->class('ux-rich-editor__separator'));
        
        // 列表与引用
        $toolbar->child($this->createBtn('List', 'bi bi-list-ul', 'insertUnorderedList'));
        $toolbar->child($this->createBtn('Quote', 'bi bi-quote', 'formatBlock', 'blockquote'));
        
        $toolbar->child(Element::make('span')->class('ux-rich-editor__separator'));
        
        // 链接（简单的弹窗处理）
        $toolbar->child(Element::make('button')
            ->class('ux-rich-editor__btn')
            ->attr('type', 'button')
            ->attr('onclick', "const url = prompt('Enter URL:'); if(url) document.execCommand('createLink', false, url)")
            ->html('<i class="bi bi-link-45deg"></i>'));

        return $toolbar;
    }

    private function createBtn(string $title, string $icon, string $cmd, string $arg = ''): Element
    {
        $argJson = $arg ? "'$arg'" : 'null';
        return Element::make('button')
            ->class('ux-rich-editor__btn')
            ->attr('type', 'button')
            ->attr('title', $title)
            ->attr('onclick', "document.execCommand('$cmd', false, $argJson)")
            ->html("<i class='$icon'></i>");
    }
}
