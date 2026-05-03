<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 分割线
 *
 * 用于分隔内容区块，支持水平/垂直方向、带文字、虚线、多种颜色变体。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Divider::make()->text('或者')
 * @ux-example Divider::make()->vertical()->dashed()
 * @ux-example Divider::make()->orientationLeft()->text('标题')->primary()
 * @ux-js-component —
 * @ux-css divider.css
 */
class Divider extends UXComponent
{
    protected ?string $text = null;
    protected string $orientation = 'center';
    protected string $type = 'horizontal';
    protected bool $dashed = false;
    protected string $variant = 'default';

    /**
     * 设置分割线文字
     * @param string $text 文字内容
     * @return static
     * @ux-example Divider::make()->text('或者')
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * 设置文字位置（仅水平模式）
     * @param string $orientation 位置：left/center/right
     * @return static
     * @ux-example Divider::make()->orientation('left')
     * @ux-default 'center'
     */
    public function orientation(string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    /**
     * 文字居左
     * @return static
     * @ux-example Divider::make()->orientationLeft()
     */
    public function orientationLeft(): static
    {
        return $this->orientation('left');
    }

    /**
     * 文字居右
     * @return static
     * @ux-example Divider::make()->orientationRight()
     */
    public function orientationRight(): static
    {
        return $this->orientation('right');
    }

    /**
     * 文字居中
     * @return static
     * @ux-example Divider::make()->orientationCenter()
     */
    public function orientationCenter(): static
    {
        return $this->orientation('center');
    }

    /**
     * 垂直分割线
     * @return static
     * @ux-example Divider::make()->vertical()
     */
    public function vertical(): static
    {
        $this->type = 'vertical';
        return $this;
    }

    /**
     * 水平分割线
     * @return static
     * @ux-example Divider::make()->horizontal()
     */
    public function horizontal(): static
    {
        $this->type = 'horizontal';
        return $this;
    }

    /**
     * 虚线样式
     * @param bool $dashed 是否虚线
     * @return static
     * @ux-example Divider::make()->dashed()
     * @ux-default true
     */
    public function dashed(bool $dashed = true): static
    {
        $this->dashed = $dashed;
        return $this;
    }

    /**
     * 设置颜色变体
     * @param string $variant 变体名：default/primary/success/warning/danger
     * @return static
     * @ux-example Divider::make()->variant('primary')
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 主色变体
     * @return static
     * @ux-example Divider::make()->primary()
     */
    public function primary(): static
    {
        return $this->variant('primary');
    }

    /**
     * 成功变体
     * @return static
     * @ux-example Divider::make()->success()
     */
    public function success(): static
    {
        return $this->variant('success');
    }

    /**
     * 警告变体
     * @return static
     * @ux-example Divider::make()->warning()
     */
    public function warning(): static
    {
        return $this->variant('warning');
    }

    /**
     * 危险变体
     * @return static
     * @ux-example Divider::make()->danger()
     */
    public function danger(): static
    {
        return $this->variant('danger');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-divider');
        $el->class("ux-divider-{$this->type}");
        $el->class("ux-divider-{$this->variant}");

        if ($this->dashed) {
            $el->class('ux-divider-dashed');
        }

        if ($this->type === 'horizontal' && $this->text) {
            $el->class("ux-divider-with-text-{$this->orientation}");
            $el->child(
                Element::make('span')
                    ->class('ux-divider-text')
                    ->text($this->text)
            );
        }

        return $el;
    }
}
