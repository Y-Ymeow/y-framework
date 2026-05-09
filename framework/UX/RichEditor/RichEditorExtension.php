<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

/**
 * 富文本编辑器扩展基类
 *
 * v2.0: 扩展可以作为 Block 的内联插件存在。
 * 旧版扩展（工具栏按钮 + 内容解析）继续兼容，
 * 新增 toBlockType() 方法可将扩展转换为 Block 类型。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example
 * class MyExtension extends RichEditorExtension {
 *     public function getName(): string { return 'myExtension'; }
 *     public function execute(string $content, array $params = []): string { return $content; }
 * }
 * @ux-example-end
 * RichEditor::make()->extension('my', new MyExtension())
 */
abstract class RichEditorExtension
{
    protected string $name = '';
    protected string $icon = '';
    protected string $label = '';
    protected string $title = '';
    protected string $category = 'common';
    protected array $config = [];

    private bool $isBlockMode = false;

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

    /**
     * 启用 Block 模式
     * 启用后，此扩展将作为独立 Block 类型注册到 BlockRegistry
     */
    public function asBlock(): static
    {
        $this->isBlockMode = true;
        return $this;
    }

    /**
     * 是否为 Block 模式
     */
    public function isBlockMode(): bool
    {
        return $this->isBlockMode;
    }

    /**
     * 将扩展转换为 BlockType
     * 子类可覆盖此方法以自定义 Block 定义
     */
    public function toBlockType(): BlockType
    {
        return BlockType::make($this->name)
            ->title($this->title ?: $this->label ?: $this->name)
            ->icon($this->icon)
            ->category($this->category)
            ->attribute('content', ['type' => 'rich-text', 'default' => '', 'source' => 'children'])
            ->withRenderElement(function (array $attrs, array $innerBlocks): Element {
                $content = $attrs['content'] ?? '';
                $parsed = $this->parse($content);
                return Element::make('div')->html($parsed);
            });
    }

    /**
     * 获取扩展的序列化定义（传给前端）
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'label' => $this->label,
            'title' => $this->title,
            'category' => $this->category,
            'isBlock' => $this->isBlockMode,
            'config' => $this->config,
        ];
    }
}
