<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 描述列表
 *
 * 用于展示键值对描述信息，支持多列布局、标题、边框、标签对齐、额外内容。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DescriptionList::make()->item('姓名', '张三')->item('邮箱', 'test@example.com')->columns(2)
 * @ux-example DescriptionList::make()->title('详细信息')->items($items)->bordered()->labelAlign('left')
 * @ux-js-component description-list.js
 * @ux-css description-list.css
 */
class DescriptionList extends UXComponent
{
    protected array $items = [];
    protected int $columns = 3;
    protected string $variant = 'default';
    protected string $size = 'md';
    protected bool $bordered = false;
    protected ?string $title = null;
    protected mixed $extra = null;
    protected string $labelAlign = 'right';
    protected ?string $fragmentName = null;

    /**
     * 添加一个描述项
     * @param string $label 标签文字
     * @param mixed $value 值
     * @param \Closure|null $render 自定义渲染回调
     * @return static
     * @ux-example DescriptionList::make()->item('姓名', '张三')->item('邮箱', 'test@example.com')
     */
    public function item(string $label, mixed $value, ?\Closure $render = null): static
    {
        $this->items[] = [
            'label' => $label,
            'value' => $value,
            'render' => $render,
        ];
        return $this;
    }

    /**
     * 批量添加描述项
     * @param array $items 描述项配置数组
     * @return static
     */
    public function items(array $items): static
    {
        foreach ($items as $item) {
            $this->item(
                $item['label'] ?? '',
                $item['value'] ?? null,
                $item['render'] ?? null
            );
        }
        return $this;
    }

    /**
     * 设置列数
     * @param int $columns 列数（至少 1）
     * @return static
     * @ux-default 3
     */
    public function columns(int $columns): static
    {
        $this->columns = max(1, $columns);
        return $this;
    }

    /**
     * 设置变体
     * @param string $variant 变体：default
     * @return static
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 小尺寸
     * @return static
     * @ux-example DescriptionList::make()->items($items)->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example DescriptionList::make()->items($items)->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置是否带边框
     * @param bool $bordered 是否带边框
     * @return static
     * @ux-default false
     */
    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    /**
     * 设置标题
     * @param string $title 标题
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置额外内容（标题右侧）
     * @param mixed $extra 自定义内容（Element 或组件）
     * @return static
     */
    public function extra(mixed $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * 设置标签对齐方式
     * @param string $align 对齐：left/right
     * @return static
     * @ux-default 'right'
     */
    public function labelAlign(string $align): static
    {
        $this->labelAlign = $align;
        return $this;
    }

    /**
     * 设置分片名称（用于 Live 局部更新）
     * @param string $name 分片名
     * @return static
     */
    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);
        $wrapper->class('ux-desc-list-wrapper');

        if ($this->title || $this->extra) {
            $headerEl = Element::make('div')->class('ux-desc-list-header');
            if ($this->title) {
                $headerEl->child(Element::make('div')->class('ux-desc-list-title')->text($this->title));
            }
            if ($this->extra) {
                $headerEl->child(Element::make('div')->class('ux-desc-list-extra')->child($this->resolveChild($this->extra)));
            }
            $wrapper->child($headerEl);
        }

        $viewEl = Element::make('div')->class('ux-desc-list');
        $viewEl->class("ux-desc-list-{$this->variant}");
        $viewEl->class("ux-desc-list-{$this->size}");
        $viewEl->class("ux-desc-list-col-{$this->columns}");

        if ($this->bordered) {
            $viewEl->class('ux-desc-list-bordered');
        }

        if ($this->fragmentName) {
            $viewEl->liveFragment($this->fragmentName);
        }

        $rowEl = null;
        $colIndex = 0;

        foreach ($this->items as $index => $item) {
            if ($colIndex % $this->columns === 0) {
                $rowEl = Element::make('div')->class('ux-desc-list-row');
                $viewEl->child($rowEl);
            }

            $itemEl = Element::make('div')->class('ux-desc-list-item');

            $labelEl = Element::make('div')
                ->class('ux-desc-list-item-label')
                ->class("ux-desc-list-label-{$this->labelAlign}")
                ->text($item['label']);
            $itemEl->child($labelEl);

            $valueEl = Element::make('div')->class('ux-desc-list-item-value');

            if (isset($item['render']) && $item['render'] instanceof \Closure) {
                $rendered = ($item['render'])($item['value'], $item);
                if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                    $valueEl->child($this->resolveChild($rendered));
                } elseif (is_string($rendered)) {
                    $valueEl->html($rendered);
                } else {
                    $valueEl->text((string)($item['value'] ?? '-'));
                }
            } else {
                $valueEl->text((string)($item['value'] ?? '-'));
            }

            $itemEl->child($valueEl);
            $rowEl->child($itemEl);

            $colIndex++;
        }

        while ($colIndex % $this->columns !== 0) {
            $emptyItem = Element::make('div')->class('ux-desc-list-item ux-desc-list-item-empty');
            $emptyItem->child(Element::make('div')->class('ux-desc-list-item-label')->text(''));
            $emptyItem->child(Element::make('div')->class('ux-desc-list-item-value')->text(''));
            $rowEl->child($emptyItem);
            $colIndex++;
        }

        $wrapper->child($viewEl);

        return $wrapper;
    }
}
