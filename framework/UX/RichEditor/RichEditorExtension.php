<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

/**
 * 富文本编辑器扩展基类
 *
 * 抽象基类，用于创建富文本编辑器的自定义扩展（工具栏按钮、内容解析等）。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example
 * class MyExtension extends RichEditorExtension {
 *     public function getName(): string { return 'myExtension'; }
 *     public function execute(string $content): string { return $content; }
 * }
 * @ux-example-end
 * RichEditor::make()->extension('my', new MyExtension())
 * @ux-js-component rich-editor.js
 * @ux-css rich-editor.css
 */
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

    /**
     * 获取工具栏按钮
     * @param string $editorId 编辑器 ID
     * @return Element|null
     */
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

    /**
     * 执行扩展逻辑（必须实现）
     * @param string $content 内容
     * @param array $params 参数
     * @return string 处理后的内容
     */
    abstract public function execute(string $content, array $params = []): string;

    /**
     * 解析内容（用于服务端解析）
     * @param string $content 内容
     * @return string 解析后的内容
     */
    public function parse(string $content): string
    {
        return $content;
    }

    /**
     * 渲染预览内容
     * @param string $content 内容
     * @return string 预览内容
     */
    public function renderPreview(string $content): string
    {
        return $content;
    }

    /**
     * 获取配置值
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置值
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @return static
     */
    public function setConfig(string $key, mixed $value): static
    {
        $this->config[$key] = $value;
        return $this;
    }
}
