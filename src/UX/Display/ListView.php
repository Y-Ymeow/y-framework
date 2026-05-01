<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class ListView extends UXComponent
{
    protected array $items = [];
    protected bool $bordered = false;
    protected bool $split = true;
    protected string $size = 'md';
    protected ?string $header = null;
    protected ?string $footer = null;
    protected bool $loading = false;

    public function item(mixed $content): static
    {
        $this->items[] = $content;
        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    public function split(bool $split = true): static
    {
        $this->split = $split;
        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function sm(): static
    {
        return $this->size('sm');
    }

    public function lg(): static
    {
        return $this->size('lg');
    }

    public function header(string $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function footer(string $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

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
