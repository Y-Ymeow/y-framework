<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * Tailwind 外观快捷方法 trait
 *
 * 提供圆角、阴影、背景、边框等外观相关的 Tailwind CSS 快捷方法。
 *
 * @view-category Tailwind 外观
 * @view-since 1.0.0
 */
trait HasTailwindAppearance
{
    /**
     * 设置圆角（rounded-{size}）
     *
     * @view-since 1.0.0
     * @param string $size 圆角大小：none, sm, DEFAULT, md, lg, xl, 2xl, full
     * @view-default lg
     * @return static
     */
    public function rounded(string $size = 'lg'): static
    {
        return $this->class("rounded-{$size}");
    }

    /**
     * 设置阴影（shadow-{size}）
     *
     * @view-since 1.0.0
     * @param string $size 阴影大小：none, sm, DEFAULT, md, lg, xl, 2xl
     * @view-default md
     * @return static
     */
    public function shadow(string $size = 'md'): static
    {
        return $this->class("shadow-{$size}");
    }

    /**
     * 设置背景色（bg-{color}）
     *
     * @view-since 1.0.0
     * @param string $color 颜色值（如 'blue-500', 'gray-100', 'white'）
     * @return static
     */
    public function bg(string $color): static
    {
        return $this->class("bg-{$color}");
    }

    /**
     * 设置边框（border border-{color}）
     *
     * @view-since 1.0.0
     * @param string $color 边框颜色（如 'gray-200', 'red-300'）
     * @view-default gray-200
     * @return static
     */
    public function border(string $color = 'gray-200'): static
    {
        return $this->class("border border-{$color}");
    }

    /**
     * 设置透明度（opacity-{level}）
     *
     * @view-since 1.0.0
     * @param int $level 透明度级别：0, 5, 10, 20, 25, 30, ..., 95, 100
     * @return static
     */
    public function opacity(int $level): static
    {
        return $this->class("opacity-{$level}");
    }

    abstract public function class(string ...$classes): static;
}
