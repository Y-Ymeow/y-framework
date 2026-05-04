<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * Tailwind 布局快捷方法 trait
 *
 * 提供 flex、grid、对齐、宽度等布局相关的 Tailwind CSS 快捷方法。
 *
 * @view-category Tailwind 布局
 * @view-since 1.0.0
 */
trait HasTailwindLayout
{
    /**
     * 设置 flex 布局（flex / flex flex-col）
     *
     * @view-since 1.0.0
     * @param string $direction 方向：'row'（默认）或 'col'
     * @view-default row
     * @return static
     */
    public function flex(string $direction = 'row'): static
    {
        return $this->class('flex' . ($direction === 'col' ? ' flex-col' : ''));
    }

    /**
     * 设置 grid 布局（grid grid-cols-{cols}）
     *
     * @view-since 1.0.0
     * @param int $cols 列数
     * @view-default 1
     * @return static
     */
    public function grid(int $cols = 1): static
    {
        return $this->class("grid grid-cols-{$cols}");
    }

    /**
     * 垂直居中对齐（items-center）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function itemsCenter(): static
    {
        return $this->class('items-center');
    }

    /**
     * 顶部对齐（items-start）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function itemsStart(): static
    {
        return $this->class('items-start');
    }

    /**
     * 底部对齐（items-end）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function itemsEnd(): static
    {
        return $this->class('items-end');
    }

    /**
     * 两端对齐（justify-between）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function justifyBetween(): static
    {
        return $this->class('justify-between');
    }

    /**
     * 水平居中（justify-center）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function justifyCenter(): static
    {
        return $this->class('justify-center');
    }

    /**
     * 右对齐（justify-end）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function justifyEnd(): static
    {
        return $this->class('justify-end');
    }

    /**
     * 宽度 100%（w-full）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function wFull(): static
    {
        return $this->class('w-full');
    }

    /**
     * 最小高度（min-h-{size}）
     *
     * @view-since 1.0.0
     * @param string $size 尺寸值（如 'screen', '0', 'full'）
     * @view-default screen
     * @return static
     */
    public function minH(string $size = 'screen'): static
    {
        return $this->class("min-h-{$size}");
    }

    /**
     * 溢出控制（overflow-{type}）
     *
     * @view-since 1.0.0
     * @param string $type 溢出类型：hidden, auto, scroll, visible
     * @view-default hidden
     * @return static
     */
    public function overflow(string $type = 'hidden'): static
    {
        return $this->class("overflow-{$type}");
    }

    abstract public function class(string ...$classes): static;
}
