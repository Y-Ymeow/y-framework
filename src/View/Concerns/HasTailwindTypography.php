<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * Tailwind 排版快捷方法 trait
 *
 * 提供字体粗细、字号、颜色、对齐等排版相关的 Tailwind CSS 快捷方法。
 *
 * @view-category Tailwind 排版
 * @view-since 1.0.0
 */
trait HasTailwindTypography
{
    /**
     * 粗体（font-bold）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function fontBold(): static
    {
        return $this->class('font-bold');
    }

    /**
     * 半粗体（font-semibold）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function fontSemibold(): static
    {
        return $this->class('font-semibold');
    }

    /**
     * 正常字重（font-normal）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function fontNormal(): static
    {
        return $this->class('font-normal');
    }

    /**
     * 小号文字（text-sm）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textSm(): static
    {
        return $this->class('text-sm');
    }

    /**
     * 超小号文字（text-xs）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textXs(): static
    {
        return $this->class('text-xs');
    }

    /**
     * 大号文字（text-lg）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textLg(): static
    {
        return $this->class('text-lg');
    }

    /**
     * 超大号文字（text-xl）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textXl(): static
    {
        return $this->class('text-xl');
    }

    /**
     * 2倍大号文字（text-2xl）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function text2xl(): static
    {
        return $this->class('text-2xl');
    }

    /**
     * 3倍大号文字（text-3xl）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function text3xl(): static
    {
        return $this->class('text-3xl');
    }

    /**
     * 灰色文字（text-gray-{shade}）
     *
     * @view-since 1.0.0
     * @param string $shade 灰度值：50, 100, 200, ..., 800, 900
     * @view-default 500
     * @return static
     */
    public function textGray(string $shade = '500'): static
    {
        return $this->class("text-gray-{$shade}");
    }

    /**
     * 白色文字（text-white）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textWhite(): static
    {
        return $this->class('text-white');
    }

    /**
     * 文字居中（text-center）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textCenter(): static
    {
        return $this->class('text-center');
    }

    /**
     * 文字右对齐（text-right）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function textRight(): static
    {
        return $this->class('text-right');
    }

    /**
     * 文字截断（truncate）
     *
     * 超长文本自动截断并显示省略号
     *
     * @view-since 1.0.0
     * @return static
     */
    public function truncate(): static
    {
        return $this->class('truncate');
    }

    /**
     * 大写字母（uppercase）
     *
     * @view-since 1.0.0
     * @return static
     */
    public function uppercase(): static
    {
        return $this->class('uppercase');
    }

    /**
     * 行高（leading-{size}）
     *
     * @view-since 1.0.0
     * @param string $size 行高值：none, tight, snug, normal, relaxed, loose
     * @view-default normal
     * @return static
     */
    public function leading(string $size = 'normal'): static
    {
        return $this->class("leading-{$size}");
    }

    abstract public function class(string ...$classes): static;
}
