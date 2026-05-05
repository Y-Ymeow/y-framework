<?php

declare(strict_types=1);

namespace Framework\UX\Layout;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 栅格布局
 *
 * 用于多列栅格布局，支持列数、间距、对齐方式。
 *
 * @ux-category Layout
 * @ux-since 1.0.0
 * @ux-example Grid::make()->cols(3)->gap(4)->child($col1)->child($col2)->child($col3)
 * @ux-example Grid::make()->cols(4)->gap(6)->alignCenter()
 * @ux-js-component —
 * @ux-css grid.css
 */
class Grid extends UXComponent
{
    protected int $cols = 1;
    protected string $gap = '4';
    protected string $align = 'stretch';

    /**
     * 设置列数
     * @param int $cols 列数
     * @return static
     * @ux-example Grid::make()->cols(3)
     * @ux-default 1
     */
    public function cols(int $cols): static
    {
        $this->cols = max(1, $cols);
        return $this;
    }

    /**
     * 设置列间距
     * @param int $gap 间距（Tailwind gap-* 值）
     * @return static
     * @ux-example Grid::make()->gap(6)
     * @ux-default '4'
     */
    public function gap(int $gap): static
    {
        $this->gap = (string)$gap;
        return $this;
    }

    /**
     * 设置列对齐方式
     * @param string $align 对齐：start/center/end/stretch
     * @return static
     * @ux-example Grid::make()->align('center')
     * @ux-default 'stretch'
     */
    public function align(string $align): static
    {
        $this->align = $align;
        return $this;
    }

    /**
     * 左对齐
     * @return static
     * @ux-example Grid::make()->alignStart()
     */
    public function alignStart(): static
    {
        return $this->align('start');
    }

    /**
     * 居中对齐
     * @return static
     * @ux-example Grid::make()->alignCenter()
     */
    public function alignCenter(): static
    {
        return $this->align('center');
    }

    /**
     * 右对齐
     * @return static
     * @ux-example Grid::make()->alignEnd()
     */
    public function alignEnd(): static
    {
        return $this->align('end');
    }

    protected function toElement(): Element
    {
        $element = new Element('div');
        $this->buildElement($element);

        $element->class('ux-grid', 'grid', "grid-cols-{$this->cols}", "gap-{$this->gap}");

        $element->children(...$this->children);

        return $element;
    }
}
