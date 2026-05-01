<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Timeline extends UXComponent
{
    protected array $items = [];
    protected bool $reverse = false;
    protected string $mode = 'left';

    public function item(string $content, ?string $label = null, ?string $dot = null, string $color = 'blue'): static
    {
        $this->items[] = [
            'content' => $content,
            'label' => $label,
            'dot' => $dot,
            'color' => $color,
        ];
        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function reverse(bool $reverse = true): static
    {
        $this->reverse = $reverse;
        return $this;
    }

    public function mode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function left(): static
    {
        return $this->mode('left');
    }

    public function right(): static
    {
        return $this->mode('right');
    }

    public function alternate(): static
    {
        return $this->mode('alternate');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-timeline');
        $el->class("ux-timeline-{$this->mode}");
        if ($this->reverse) {
            $el->class('ux-timeline-reverse');
        }

        $items = $this->reverse ? array_reverse($this->items) : $this->items;

        foreach ($items as $index => $item) {
            $itemEl = Element::make('div')->class('ux-timeline-item');

            // 标签（时间）
            if ($item['label']) {
                $labelEl = Element::make('div')
                    ->class('ux-timeline-item-label')
                    ->text($item['label']);
                $itemEl->child($labelEl);
            }

            // 圆点
            $dotEl = Element::make('div')
                ->class('ux-timeline-item-dot')
                ->class("ux-timeline-item-dot-{$item['color']}");

            if ($item['dot']) {
                $dotEl->html($item['dot']);
                $dotEl->class('ux-timeline-item-dot-custom');
            }

            $itemEl->child($dotEl);

            // 连接线
            if ($index < count($items) - 1) {
                $lineEl = Element::make('div')->class('ux-timeline-item-line');
                $itemEl->child($lineEl);
            }

            // 内容
            $contentEl = Element::make('div')
                ->class('ux-timeline-item-content')
                ->html($item['content']);
            $itemEl->child($contentEl);

            $el->child($itemEl);
        }

        return $el;
    }
}
