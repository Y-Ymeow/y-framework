<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 骨架屏
 *
 * 用于内容加载前的占位显示，支持文本、头像、矩形、圆形等多种类型，带动画效果。
 *
 * @ux-category Feedback
 * @ux-since 1.0.0
 * @ux-example Skeleton::make()->text()->count(3)
 * @ux-example Skeleton::make()->avatar()->width('100px')->height('100px')
 * @ux-js-component —
 * @ux-css skeleton.css
 */
class Skeleton extends UXComponent
{
    protected string $type = 'text';
    protected int $count = 1;
    protected bool $animated = true;
    protected ?string $width = null;
    protected ?string $height = null;

    /**
     * 设置骨架屏类型
     * @param string $type 类型：text/avatar/rect/circle
     * @return static
     * @ux-example Skeleton::make()->type('avatar')
     * @ux-default 'text'
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 文本类型
     * @return static
     * @ux-example Skeleton::make()->text()
     */
    public function text(): static
    {
        return $this->type('text');
    }

    /**
     * 头像类型
     * @return static
     * @ux-example Skeleton::make()->avatar()
     */
    public function avatar(): static
    {
        return $this->type('avatar');
    }

    /**
     * 矩形类型
     * @return static
     * @ux-example Skeleton::make()->rect()
     */
    public function rect(): static
    {
        return $this->type('rect');
    }

    /**
     * 圆形类型
     * @return static
     * @ux-example Skeleton::make()->circle()
     */
    public function circle(): static
    {
        return $this->type('circle');
    }

    /**
     * 设置重复数量
     * @param int $count 数量
     * @return static
     * @ux-example Skeleton::make()->count(3)
     * @ux-default 1
     */
    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * 设置是否带动画
     * @param bool $animated 是否带动画
     * @return static
     * @ux-example Skeleton::make()->animated(false)
     * @ux-default true
     */
    public function animated(bool $animated = true): static
    {
        $this->animated = $animated;
        return $this;
    }

    /**
     * 设置宽度
     * @param string $width 宽度（如 100px, 50%）
     * @return static
     * @ux-example Skeleton::make()->width('200px')
     */
    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * 设置高度
     * @param string $height 高度（如 100px, 50%）
     * @return static
     * @ux-example Skeleton::make()->height('100px')
     */
    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    protected function toElement(): Element
    {
        $containerEl = new Element('div');
        $this->buildElement($containerEl);

        for ($i = 0; $i < $this->count; $i++) {
            $el = new Element('div');
            $el->class('ux-skeleton');
            $el->class("ux-skeleton-{$this->type}");

            if ($this->animated) {
                $el->class('ux-skeleton-animated');
            }

            $style = '';
            if ($this->width) {
                $style .= "width: {$this->width};";
            }
            if ($this->height) {
                $style .= "height: {$this->height};";
            }
            if ($style) {
                $el->style($style);
            }

            $containerEl->child($el);
        }

        return $containerEl;
    }
}
