<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 时间线
 *
 * 用于展示时间线，支持多节点、标签、自定义圆点、颜色、左右/交错布局、反向。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Timeline::make()->item('事件1', '2024-01-01')->item('事件2', '2024-02-01')
 * @ux-example Timeline::make()->item('事件', null, '✓', 'green')->reverse()
 * @ux-example Timeline::make()->item('左', '2024-01')->item('右', '2024-02')->mode('alternate')
 * @ux-js-component —
 * @ux-css timeline.css
 */
class Timeline extends UXComponent
{
    protected array $items = [];
    protected bool $reverse = false;
    protected string $mode = 'left';

    /**
     * 添加时间线节点
     * @param string $content 内容
     * @param string|null $label 标签（如时间）
     * @param string|null $dot 自定义圆点
     * @param string $color 颜色
     * @return static
     * @ux-example Timeline::make()->item('事件1', '2024-01-01')
     */
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

    /**
     * 批量设置时间线节点
     * @param array $items 节点数组
     * @return static
     * @ux-example Timeline::make()->items($items)
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * 设置是否反向显示（从下到上）
     * @param bool $reverse 是否反向
     * @return static
     * @ux-example Timeline::make()->reverse()
     * @ux-default true
     */
    public function reverse(bool $reverse = true): static
    {
        $this->reverse = $reverse;
        return $this;
    }

    /**
     * 设置布局模式
     * @param string $mode 模式：left/right/alternate
     * @return static
     * @ux-example Timeline::make()->mode('alternate')
     * @ux-default 'left'
     */
    public function mode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * 左侧布局
     * @return static
     * @ux-example Timeline::make()->left()
     */
    public function left(): static
    {
        return $this->mode('left');
    }

    /**
     * 右侧布局
     * @return static
     * @ux-example Timeline::make()->right()
     */
    public function right(): static
    {
        return $this->mode('right');
    }

    /**
     * 交错布局（左右交替）
     * @return static
     * @ux-example Timeline::make()->alternate()
     */
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
