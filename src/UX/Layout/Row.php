<?php

declare(strict_types=1);

namespace Framework\UX\Layout;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\View\Element\Container;

/**
 * 行布局
 *
 * 用于水平行布局，支持对齐方式、间距、换行、两端对齐。
 *
 * @ux-category Layout
 * @ux-since 1.0.0
 * @ux-example Row::make()->justifyBetween()->alignCenter()->gap(4)->child($left)->child($right)
 * @ux-example Row::make()->justifyCenter()->gap(2)->wrap()->child($items)
 * @ux-js-component —
 * @ux-css row.css
 */
class Row extends UXComponent
{
    protected string $justify = 'start';
    protected string $align = 'center';
    protected string $gap = '2';
    protected bool $wrap = true;

    /**
     * 设置主轴对齐（水平方向）
     * @param string $justify 对齐方式：start/center/end/between
     * @return static
     * @ux-example Row::make()->justify('center')
     * @ux-default 'start'
     */
    public function justify(string $justify): static
    {
        $this->justify = $justify;
        return $this;
    }

    /**
     * 左对齐
     * @return static
     * @ux-example Row::make()->justifyStart()
     */
    public function justifyStart(): static
    {
        return $this->justify('start');
    }

    /**
     * 居中对齐
     * @return static
     * @ux-example Row::make()->justifyCenter()
     */
    public function justifyCenter(): static
    {
        return $this->justify('center');
    }

    /**
     * 右对齐
     * @return static
     * @ux-example Row::make()->justifyEnd()
     */
    public function justifyEnd(): static
    {
        return $this->justify('end');
    }

    /**
     * 两端对齐（首尾）
     * @return static
     * @ux-example Row::make()->justifyBetween()
     */
    public function justifyBetween(): static
    {
        return $this->justify('between');
    }

    /**
     * 设置交叉轴对齐（垂直方向）
     * @param string $align 对齐方式：start/center/end
     * @return static
     * @ux-example Row::make()->align('center')
     * @ux-default 'center'
     */
    public function align(string $align): static
    {
        $this->align = $align;
        return $this;
    }

    /**
     * 顶部对齐
     * @return static
     * @ux-example Row::make()->alignStart()
     */
    public function alignStart(): static
    {
        return $this->align('start');
    }

    /**
     * 居中对齐
     * @return static
     * @ux-example Row::make()->alignCenter()
     */
    public function alignCenter(): static
    {
        return $this->align('center');
    }

    /**
     * 底部对齐
     * @return static
     * @ux-example Row::make()->alignEnd()
     */
    public function alignEnd(): static
    {
        return $this->align('end');
    }

    /**
     * 设置间距
     * @param int $gap 间距（Tailwind gap-* 值）
     * @return static
     * @ux-example Row::make()->gap(4)
     * @ux-default '2'
     */
    public function gap(int $gap): static
    {
        $this->gap = (string)$gap;
        return $this;
    }

    /**
     * 设置是否换行
     * @param bool $wrap 是否换行
     * @return static
     * @ux-example Row::make()->wrap(true)
     * @ux-default true
     */
    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;
        return $this;
    }

    /**
     * 不换行
     * @return static
     * @ux-example Row::make()->noWrap()
     */
    public function noWrap(): static
    {
        return $this->wrap(false);
    }

    protected function toElement(): Element
    {
        $classes = ['ux-row', 'flex', 'flex-row', "justify-{$this->justify}", "items-{$this->align}", "gap-{$this->gap}"];
        if ($this->wrap) {
            $classes[] = 'flex-wrap';
        }
        $this->classes = array_merge($this->classes, $classes);
        
        $el = Container::make()
            ->class(...$this->classes)
            ->attrs($this->attrs)
            ->children(...$this->children);
        return $el;
    }
}
