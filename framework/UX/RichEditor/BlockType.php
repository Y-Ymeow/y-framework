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
 * ## 两种扩展方式
 *
 * ### 1. 链式调用（简单场景）
 * BlockType::make('callout')
 *     ->title('标注')
 *     ->category('common')
 *     ->attribute('text', ['type' => 'string', 'default' => ''])
 *     ->attribute('type', ['type' => 'string', 'default' => 'info'])
 *     ->withRenderElement(function(array $attrs, array $innerBlocks): Element {
 *         return Element::make('div')
 *             ->class('callout', 'callout--' . ($attrs['type'] ?? 'info'))
 *             ->text($attrs['text'] ?? '');
 *     })
 *
 * ### 2. 类继承（复杂场景，推荐）
 * class CalloutBlock extends BlockType
 * {
 *     public function renderElement(array $attributes, array $innerBlocks = []): Element
 *     {
 *         return Element::make('div')
 *             ->class('callout', 'callout--' . ($attributes['type'] ?? 'info'))
 *             ->child(Element::make('span')->class('callout__icon')->text('💡'))
 *             ->child(Element::make('span')->class('callout__text')->text($attributes['text'] ?? ''));
 *     }
 * }
 * BlockRegistry::register('callout', new CalloutBlock('callout'));
 *
 * @ux-category RichEditor
 * @ux-since 2.0.0
 */
class BlockType
{
    public string $name = '';
    public string $title = '';
    public string $icon = '';
    public string $category = 'common';
    public array $attributes = [];
    public bool $supportsInnerBlocks = false;
    public bool $supportsInlineFormats = false;
    public array $inlineFormats = [];

    private ?\Closure $renderElementCallback = null;
    private ?\Closure $editFormCallback = null;

    public function __construct(string $name = '')
    {
        if ($name !== '') {
            $this->name = $name;
        }
    }

    public static function make(string $name): static
    {
        return new static($name);
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

    public function supportsInlineFormats(bool $supports = true): static
    {
        $this->supportsInlineFormats = $supports;
        return $this;
    }

    public function withInlineFormats(string ...$formats): static
    {
        $this->supportsInlineFormats = true;
        $this->inlineFormats = $formats;
        return $this;
    }

    /**
     * 设置渲染回调（返回 Element）
     *
     * 回调签名: function(array $attributes, array $innerBlocks): Element
     */
    public function withRenderElement(\Closure $callback): static
    {
        $this->renderElementCallback = $callback;
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
     * 渲染 Block 为 Element
     *
     * 子类可覆盖此方法实现自定义渲染逻辑。
     * 优先级：renderElement 回调 > 子类覆盖 > 默认渲染
     */
    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        if ($this->renderElementCallback) {
            return ($this->renderElementCallback)($attributes, $innerBlocks);
        }

        return $this->defaultRenderElement($attributes, $innerBlocks);
    }

    /**
     * 服务端渲染 Block 为 HTML
     */
    public function renderBlock(array $attributes, array $innerBlocks = []): string
    {
        $element = $this->renderElement($attributes, $innerBlocks);

        if ($element instanceof Element) {
            return $element->render();
        }

        return '';
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
            'supportsInlineFormats' => $this->supportsInlineFormats,
            'inlineFormats' => $this->inlineFormats,
            'defaultAttributes' => $this->getDefaultAttributes(),
        ];
    }

    /**
     * 默认渲染 Element（子类可覆盖）
     */
    protected function defaultRenderElement(array $attributes, array $innerBlocks = []): Element
    {
        $tag = match ($this->name) {
            'paragraph' => 'p',
            'heading' => 'h' . ($attributes['level'] ?? 2),
            'quote' => 'blockquote',
            'code' => 'pre',
            'list' => ($attributes['ordered'] ?? false) ? 'ol' : 'ul',
            default => 'div',
        };

        $el = Element::make($tag);

        if ($this->supportsInnerBlocks && !empty($innerBlocks)) {
            foreach ($innerBlocks as $block) {
                $childHtml = BlockRegistry::renderBlock(
                    $block['blockName'],
                    $block['attributes'] ?? [],
                    $block['innerBlocks'] ?? []
                );
                $el->child($childHtml);
            }
        } else {
            $content = $attributes['content'] ?? '';
            if (is_string($content) && $content !== '') {
                $el->html($content);
            }
        }

        return $el;
    }
}
