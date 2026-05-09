<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

/**
 * Block 类型定义
 *
 * 定义一个 Block 的结构、属性、渲染方式。
 * 在 PHP 端注册，前端动态获取定义。
 *
 * @ux-category RichEditor
 * @ux-since 2.0.0
 * @ux-example
 * BlockRegistry::register('image', BlockType::make('image')
 *     ->title('图片')
 *     ->icon('<svg>...</svg>')
 *     ->category('media')
 *     ->attribute('src', ['type' => 'string', 'source' => 'attribute'])
 *     ->attribute('alt', ['type' => 'string', 'default' => ''])
 *     ->render(fn($attrs) => '<img src="'.$attrs['src'].'" alt="'.$attrs['alt'].'">')
 * );
 */
class BlockType
{
    public string $name = '';
    public string $title = '';
    public string $icon = '';
    public string $category = 'common';
    public array $attributes = [];
    public bool $supportsInnerBlocks = false;

    private ?\Closure $renderCallback = null;
    private ?\Closure $editFormCallback = null;

    public static function make(string $name): static
    {
        $instance = new static();
        $instance->name = $name;
        return $instance;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function category(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * 定义属性 schema
     * @param string $name 属性名
     * @param array $schema 类型定义: [type, default, source]
     *   - type: string|number|boolean|array|object|rich-text
     *   - default: 默认值
     *   - source: attribute|children|html|text|query
     */
    public function attribute(string $name, array $schema): static
    {
        $this->attributes[$name] = array_merge([
            'type' => 'string',
            'default' => null,
            'source' => 'attribute',
        ], $schema);
        return $this;
    }

    public function supportsInnerBlocks(bool $supports = true): static
    {
        $this->supportsInnerBlocks = $supports;
        return $this;
    }

    /**
     * 设置服务端渲染回调
     */
    public function render(\Closure $callback): static
    {
        $this->renderCallback = $callback;
        return $this;
    }

    /**
     * 设置编辑表单回调（返回 Element 数组或 Element）
     */
    public function editForm(\Closure $callback): static
    {
        $this->editFormCallback = $callback;
        return $this;
    }

    /**
     * 服务端渲染 Block 为 HTML
     */
    public function renderBlock(array $attributes, array $innerBlocks = []): string
    {
        if ($this->renderCallback) {
            return ($this->renderCallback)($attributes, $innerBlocks);
        }

        return $this->defaultRender($attributes, $innerBlocks);
    }

    /**
     * 获取编辑表单 Element
     * @return Element|array<Element>|null
     */
    public function getEditForm(array $attributes): Element|array|null
    {
        if ($this->editFormCallback) {
            return ($this->editFormCallback)($attributes);
        }
        return null;
    }

    /**
     * 获取默认属性值
     */
    public function getDefaultAttributes(): array
    {
        $defaults = [];
        foreach ($this->attributes as $name => $schema) {
            $defaults[$name] = $schema['default'] ?? match ($schema['type']) {
                'boolean' => false,
                'number' => 0,
                'array' => [],
                'object' => new \stdClass(),
                default => '',
            };
        }
        return $defaults;
    }

    /**
     * 序列化为前端可用的定义数组
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'icon' => $this->icon,
            'category' => $this->category,
            'attributes' => $this->attributes,
            'supportsInnerBlocks' => $this->supportsInnerBlocks,
            'defaultAttributes' => $this->getDefaultAttributes(),
        ];
    }

    /**
     * 默认渲染（子类可覆盖）
     */
    protected function defaultRender(array $attributes, array $innerBlocks): string
    {
        $tag = match ($this->name) {
            'paragraph' => 'p',
            'heading' => 'h' . ($attributes['level'] ?? 2),
            'quote' => 'blockquote',
            'code' => 'pre',
            'list' => $attributes['ordered'] ?? false ? 'ol' : 'ul',
            default => 'div',
        };

        $content = $attributes['content'] ?? '';

        if ($this->supportsInnerBlocks && !empty($innerBlocks)) {
            $innerHtml = '';
            foreach ($innerBlocks as $block) {
                $innerHtml .= BlockRegistry::renderBlock($block['blockName'], $block['attributes'] ?? [], $block['innerBlocks'] ?? []);
            }
            $content = $innerHtml;
        }

        return "<{$tag}>" . htmlspecialchars((string)$content) . "</{$tag}>";
    }
}
