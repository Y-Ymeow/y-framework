<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\View\Base\Element;

/**
 * 实时富文本编辑器
 *
 * 基于 LiveComponent 的实时富文本编辑器，支持工具栏、最小化模式、输出格式、扩展。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example LiveRichEditor::make()->name('content')->label('内容')->toolbar(['bold', 'italic', 'link'])
 * @ux-example LiveRichEditor::make()->name('bio')->minimal()->placeholder('个人简介')
 * @ux-js-component rich-editor.js
 * @ux-css rich-editor.css
 */
class LiveRichEditor extends LiveComponent
{
    public string $name = '';
    public string $value = '';
    public string $label = '';
    public string $placeholder = '';
    public array $toolbar = ['bold', 'italic', 'underline', 'strike', '|', 'heading', 'quote', 'code', '|', 'list', 'link'];
    public bool $minimal = false;
    public bool $required = false;
    public bool $disabled = false;
    public int $rows = 10;
    public string $outputFormat = 'html';
    public array $extensions = [];

    /**
     * 渲染编辑器
     * @return string|Element
     */
    public function render(): string|Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        if ($this->label) {
            $labelEl = Element::make('label')
                ->class('ux-form-label')
                ->attr('for', $this->name)
                ->text($this->label);

            if ($this->required) {
                $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
            }
            $groupEl->child($labelEl);
        }

        $editorId = $this->name . '-' . $this->componentId;
        $inputId = $this->name;

        $wrapperEl = Element::make('div')
            ->class('ux-rich-editor' . ($this->minimal ? ' ux-rich-editor--minimal' : ''))
            ->data('editor-id', $editorId)
            ->data('live-editor', 'true');

        if (!$this->minimal) {
            $toolbarEl = $this->buildToolbar($editorId);
            $wrapperEl->child($toolbarEl);
        }

        $editorAreaEl = Element::make('div')
            ->class('ux-rich-editor__area')
            ->attr('contenteditable', $this->disabled ? 'false' : 'true')
            ->attr('id', $editorId)
            ->data('input-id', $inputId)
            ->data('model', $this->name);

        if ($this->placeholder) {
            $editorAreaEl->attr('data-placeholder', $this->placeholder);
        }

        if ($this->value) {
            $editorAreaEl->html($this->value);
        }

        $wrapperEl->child($editorAreaEl);

        $hiddenInput = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('id', $inputId)
            ->attr('name', $this->name)
            ->bindModel($this->name);

        $hiddenInput->attr('value', $this->value);

        if ($this->required) {
            $hiddenInput->attr('required', 'required');
        }

        if ($this->disabled) {
            $hiddenInput->attr('disabled', 'disabled');
        }

        $wrapperEl->child($hiddenInput);
        $groupEl->child($wrapperEl);

        return $groupEl;
    }

    /**
     * 构建工具栏
     * @param string $editorId 编辑器 ID
     * @return Element
     */
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

        return $toolbarEl;
    }

    /**
     * 获取工具栏按钮配置
     * @param string $action 动作名称
     * @return array|null
     */
    protected function getToolbarButtonConfig(string $action): ?array
    {
        $configs = [
            'bold' => ['title' => t('editor.bold'), 'label' => 'B', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6V4zm0 8h9a4 4 0 014 4 4 4 0 01-4 4H6v-8z"/></svg>'],
            'italic' => ['title' => t('editor.italic'), 'label' => 'I', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10 4v3h2.21l-3.42 10H6v3h8v-3h-2.21l3.42-10H18V4h-8z"/></svg>'],
            'underline' => ['title' => t('editor.underline'), 'label' => 'U', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>'],
            'strike' => ['title' => t('editor.strike'), 'label' => 'S', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M17.75 9L14 4.5l-6 7.5H6l-2 3h4.5l-3 4h12.5l3-4H13l5.5-6.5-1.75-2z"/></svg>'],
            'heading' => ['title' => t('editor.heading'), 'label' => 'H', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M5 4v12h2V9h4v7h2V4h-2v5H7V4H5zm13 4h-2v2h-2v2h2v6h2v-6h2V8h-2V6z"/></svg>'],
            'quote' => ['title' => t('editor.quote'), 'label' => '"', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>'],
            'code' => ['title' => t('editor.code'), 'label' => '</>', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>'],
            'list' => ['title' => t('editor.list'), 'label' => '•', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>'],
            'link' => ['title' => t('editor.link'), 'label' => '🔗', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'],
            'undo' => ['title' => t('editor.undo'), 'label' => '↩', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>'],
            'redo' => ['title' => t('editor.redo'), 'label' => '↪', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>'],
        ];

        return $configs[$action] ?? null;
    }

    /**
     * 更新内容（LiveAction）
     * @param array $params 参数（content）
     */
    #[LiveAction]
    public function updateContent(array $params): void
    {
        $this->value = $params['content'] ?? '';
        $this->refresh();
    }

    /**
     * 插入文本（LiveAction）
     * @param array $params 参数（text）
     */
    #[LiveAction]
    public function insertText(array $params): void
    {
        $text = $params['text'] ?? '';
        $this->value .= $text;
        $this->refresh();
    }

    /**
     * 清空内容（LiveAction）
     */
    #[LiveAction]
    public function clear(): void
    {
        $this->value = '';
        $this->refresh();
    }
}
