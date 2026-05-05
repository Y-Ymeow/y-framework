<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 列表
 *
 * 用于展示列表数据，支持表头、表尾、边框、分割线、尺寸、加载状态。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example ListView::make()->item('项目1')->item('项目2')->item('项目3')
 * @ux-example ListView::make()->items($items)->bordered()->split(false)
 * @ux-example ListView::make()->header('标题')->footer('页脚')->loading()
 * @ux-js-component —
 * @ux-css list.css
 */
class ListView extends UXComponent
{
    protected array $items = [];
    protected bool $bordered = false;
    protected bool $split = true;
    protected string $size = 'md';
    protected ?string $header = null;
    protected ?string $footer = null;
    protected bool $loading = false;

    /**
     * 添加单个列表项
     * @param mixed $content 内容（字符串/组件）
     * @return static
     * @ux-example ListView::make()->item('项目1')
     */
    public function item(mixed $content): static
    {
        $this->items[] = $content;
        return $this;
    }

    /**
     * 批量设置列表项
     * @param array $items 列表项数组
     * @return static
     * @ux-example ListView::make()->items($items)
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * 启用边框
     * @param bool $bordered 是否带边框
     * @return static
     * @ux-example ListView::make()->bordered()
     * @ux-default true
     */
    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    /**
     * 设置是否显示项间分割线
     * @param bool $split 是否分割
     * @return static
     * @ux-example ListView::make()->split(false)
     * @ux-default true
     */
    public function split(bool $split = true): static
    {
        $this->split = $split;
        return $this;
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-example ListView::make()->size('lg')
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
     * @ux-example ListView::make()->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example ListView::make()->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置列表头
     * @param string $header 头部内容
     * @return static
     * @ux-example ListView::make()->header('标题')
     */
    public function header(string $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 设置列表尾
     * @param string $footer 尾部内容
     * @return static
     * @ux-example ListView::make()->footer('页脚')
     */
    public function footer(string $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * 设置加载状态
     * @param bool $loading 是否加载中
     * @return static
     * @ux-example ListView::make()->loading()
     * @ux-default true
     */
    public function loading(bool $loading = true): static
    {
        $this->loading = $loading;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-list');
        $el->class("ux-list-{$this->size}");

        if ($this->bordered) {
            $el->class('ux-list-bordered');
        }
        if (!$this->split) {
            $el->class('ux-list-no-split');
        }
        if ($this->loading) {
            $el->class('ux-list-loading');
        }

        // 头部
        if ($this->header) {
            $headerEl = Element::make('div')
                ->class('ux-list-header')
                ->html($this->header);
            $el->child($headerEl);
        }

        // 列表项
        $itemsEl = Element::make('div')->class('ux-list-items');

        foreach ($this->items as $item) {
            $itemEl = Element::make('div')->class('ux-list-item');

            if (is_string($item)) {
                $itemEl->html($item);
            } elseif ($item instanceof UXComponent) {
                $itemEl->child($item->toElement());
            } elseif ($item instanceof Element) {
                $itemEl->child($item);
            }

            $itemsEl->child($itemEl);
        }

        $el->child($itemsEl);

        // 底部
        if ($this->footer) {
            $footerEl = Element::make('div')
                ->class('ux-list-footer')
                ->html($this->footer);
            $el->child($footerEl);
        }

        return $el;
    }
}
