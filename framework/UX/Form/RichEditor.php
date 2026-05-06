<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\UX\RichEditor\RichEditorExtension;
use Framework\UX\RichEditor\ExtensionRegistry;
use Framework\CSS\RichEditorRules;

/**
 * 富文本编辑器
 *
 * 用于富文本内容编辑，支持工具栏、扩展、输出格式（HTML/Markdown/Text）、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example RichEditor::make()->name('content')->label('内容')->toolbar(['bold', 'italic', 'link', 'image'])
 * @ux-example RichEditor::make()->name('article')->label('文章')->minimal()->placeholder('开始写作...')
 * @ux-js-component rich-editor.js
 * @ux-css rich-editor.css
 */
class RichEditor extends FormField
{
    protected static ?string $componentName = 'richEditor';

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
        AssetRegistry::getInstance()->inlineStyle('ux:rich-editor', RichEditorRules::getStyles());
    }

    protected function init(): void
    {
        // RichEditor JS 较复杂，通过外部文件加载，但注册到 UX 以便 hookLive 能找到
        $this->registerJs('richEditor', '
            const RichEditor = {
                editors: new Map(),
                init() {
                    document.querySelectorAll(".ux-rich-editor[data-editor-id]").forEach(el => {
                        if (!this.editors.has(el.dataset.editorId)) this.initEditor(el);
                    });
                },
                initEditor(el) {
                    const editorId = el.dataset.editorId;
                    const textarea = el.querySelector("textarea");
                    const content = el.querySelector(".ux-rich-editor-content");
                    if (!content) return;
                    const toolbar = el.querySelector(".ux-rich-editor-toolbar");
                    if (toolbar) {
                        toolbar.addEventListener("click", (e) => {
                            const btn = e.target.closest("[data-command]");
                            if (btn) {
                                e.preventDefault();
                                this.exec(editorId, btn.dataset.command, btn.dataset.value);
                            }
                        });
                    }
                    content.addEventListener("input", () => {
                        if (textarea) textarea.value = content.innerHTML;
                        el.dispatchEvent(new CustomEvent("ux:change", { detail: { value: content.innerHTML }, bubbles: true }));
                    });
                    content.addEventListener("keydown", (e) => {
                        if (e.key === "Enter" && !e.shiftKey) {
                            e.preventDefault();
                            document.execCommand("insertParagraph", false, null);
                        }
                    });
                    this.editors.set(editorId, { el, content, textarea });
                },
                exec(editorId, command, value = null) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    editor.content.focus();
                    if (command === "heading") document.execCommand("formatBlock", false, value || "H2");
                    else if (command === "code") document.execCommand("formatBlock", false, "PRE");
                    else if (command === "link") {
                        const url = prompt("输入链接地址:");
                        if (url) document.execCommand("createLink", false, url);
                    } else if (command === "image") {
                        const url = prompt("输入图片地址:");
                        if (url) document.execCommand("insertImage", false, url);
                    } else document.execCommand(command, false, value);
                    if (editor.textarea) editor.textarea.value = editor.content.innerHTML;
                },
                getValue(editorId) {
                    const editor = this.editors.get(editorId);
                    return editor ? editor.content.innerHTML : "";
                },
                setValue(editorId, value) {
                    const editor = this.editors.get(editorId);
                    if (editor) {
                        editor.content.innerHTML = value;
                        if (editor.textarea) editor.textarea.value = value;
                    }
                },
                liveHandler(op) {
                    if (op.action === "exec" && op.editorId) this.exec(op.editorId, op.command, op.value);
                    else if (op.action === "setValue" && op.id) this.setValue(op.id, op.value);
                    else if (op.action === "getValue" && op.id) this.getValue(op.id);
                    else if (typeof this[op.action] === "function") this[op.action](op.id, op.value);
                }
            };
            return RichEditor;
        ');
    }

    /**
     * 绑定 Live 属性
     * @param string $name LiveComponent 属性名
     * @return static
     * @ux-example RichEditor::make()->liveModel('content')
     */
    public function liveModel(string $name): static
    {
        $this->liveModel = $name;
        return $this;
    }

    /**
     * 设置 LiveAction 和触发事件
     * @param string $action Action 名称
     * @param string $event 触发事件
     * @return static
     * @ux-example RichEditor::make()->liveAction('updateContent', 'change')
     * @ux-default event='change'
     */
    public function liveAction(string $action, string $event = 'change'): static
    {
        $this->liveAction = $action;
        $this->liveEvent = $event;
        return $this;
    }

    /**
     * 设置编辑器行数
     * @param int $rows 行数
     * @return static
     * @ux-example RichEditor::make()->rows(15)
     * @ux-default 10
     */
    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * 设置工具栏项目
     * @param array $items 工具栏项目数组
     * @return static
     * @ux-example RichEditor::make()->toolbar(['bold', 'italic', 'link'])
     */
    public function toolbar(array $items): static
    {
        $this->toolbar = $items;
        return $this;
    }

    /**
     * 启用最小化模式（隐藏工具栏，无边框）
     * @param bool $minimal 是否最小化
     * @return static
     * @ux-example RichEditor::make()->minimal()
     * @ux-default true
     */
    public function minimal(bool $minimal = true): static
    {
        $this->minimal = $minimal;
        if ($minimal) {
            $this->border = false;
        }
        return $this;
    }

    /**
     * 设置是否显示边框
     * @param bool $border 是否显示边框
     * @return static
     * @ux-example RichEditor::make()->border(false)
     * @ux-default true
     */
    public function border(bool $border = true): static
    {
        $this->border = $border;
        return $this;
    }

    /**
     * 设置编辑器宽度
     * @param string $width 宽度（如 100%, 500px）
     * @return static
     * @ux-example RichEditor::make()->width('100%')
     */
    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * 设置编辑器高度
     * @param string $height 高度（如 300px）
     * @return static
     * @ux-example RichEditor::make()->height('300px')
     */
    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 设置占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example RichEditor::make()->placeholder('开始写作...')
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 设置输出格式
     * @param string $format 格式：html/markdown/text
     * @return static
     * @ux-example RichEditor::make()->outputFormat('markdown')
     * @ux-default 'html'
     */
    public function outputFormat(string $format): static
    {
        $this->outputFormat = in_array($format, ['html', 'markdown', 'text']) ? $format : 'html';
        return $this;
    }

    /**
     * 注册编辑器扩展
     * @param string $name 扩展名称
     * @param RichEditorExtension $extension 扩展实例
     * @return static
     * @ux-example RichEditor::make()->extension('emoji', new EmojiExtension())
     */
    public function extension(string $name, RichEditorExtension $extension): static
    {
        $this->extensions[$name] = $extension;
        return $this;
    }

    /**
     * 注册内容解析器
     * @param callable $parser 解析器回调
     * @param string $name 解析器名称
     * @return static
     * @ux-example RichEditor::make()->parser(fn($c) => Markdown::parse($c), 'markdown')
     */
    public function parser(callable $parser, string $name = 'default'): static
    {
        $this->parsers[$name] = $parser;
        return $this;
    }

    /**
     * 解析内容（应用扩展和解析器）
     * @param string $content 内容
     * @param string $parserName 解析器名称
     * @return string 解析后的内容
     */
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

    /**
     * 清洗内容（移除危险标签和脚本）
     * @param string $content 内容
     * @return string 清洗后的内容
     */
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

    /**
     * @ux-internal
     */
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
            $hiddenInput->liveAction($this->liveAction, $this->liveEvent, $this->liveParams);
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

    /**
     * 构建工具栏
     * @param string $editorId 编辑器 ID
     * @return Element
     * @ux-internal
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

        foreach ($this->extensions as $name => $extension) {
            $extBtn = $extension->getToolbarButton($editorId);
            if ($extBtn) {
                $toolbarEl->child($extBtn);
            }
        }

        return $toolbarEl;
    }

    /**
     * 获取工具栏按钮配置
     * @param string $action 动作名称
     * @return array|null
     * @ux-internal
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
            'image' => ['title' => t('editor.image'), 'label' => '📷', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>'],
            'undo' => ['title' => t('editor.undo'), 'label' => '↩', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>'],
            'redo' => ['title' => t('editor.redo'), 'label' => '↪', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>'],
            'clear' => ['title' => t('editor.clear'), 'label' => '✕', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 3v18h18V3H3zm16 16H5V5h14v14zM11 7h2v6h-2V7zm0 8h2v2h-2v-2z"/></svg>'],
        ];

        return $configs[$action] ?? null;
    }
}
