<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * Tailwind 间距快捷方法 trait
 *
 * 提供 padding、margin、gap 等间距相关的 Tailwind CSS 快捷方法。
 *
 * @view-category Tailwind 间距
 * @view-since 1.0.0
 */
trait HasTailwindSpacing
{
    /**
     * 设置 padding（p-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function p(int $size = 4): static
    {
        return $this->class("p-{$size}");
    }

    /**
     * 设置水平 padding（px-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function px(int $size = 4): static
    {
        return $this->class("px-{$size}");
    }

    /**
     * 设置垂直 padding（py-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function py(int $size = 4): static
    {
        return $this->class("py-{$size}");
    }

    /**
     * 设置 margin（m-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function m(int $size = 4): static
    {
        return $this->class("m-{$size}");
    }

    /**
     * 设置水平 margin（mx-{size}）
     *
     * @view-since 1.0.0
     * @param string $size 间距值或 'auto'
     * @view-default auto
     * @return static
     */
    public function mx(string $size = 'auto'): static
    {
        return $this->class("mx-{$size}");
    }

    /**
     * 设置垂直 margin（my-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function my(int $size = 4): static
    {
        return $this->class("my-{$size}");
    }

    /**
     * 设置 flex/grid gap（gap-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function gap(int $size = 4): static
    {
        return $this->class("gap-{$size}");
    }

    /**
     * 设置垂直子元素间距（space-y-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function spaceY(int $size = 4): static
    {
        return $this->class("space-y-{$size}");
    }

    /**
     * 设置水平子元素间距（space-x-{size}）
     *
     * @view-since 1.0.0
     * @param int $size 间距值
     * @view-default 4
     * @return static
     */
    public function spaceX(int $size = 4): static
    {
        return $this->class("space-x-{$size}");
    }

    abstract public function class(string ...$classes): static;
}
