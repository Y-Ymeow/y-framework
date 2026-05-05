<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 徽标
 *
 * 用于显示小标签或角标，支持多种颜色变体、尺寸、胶囊形状、圆点模式。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Badge::make('99')->primary()
 * @ux-example Badge::make()->dot()->primary()
 * @ux-js-component —
 * @ux-css badge.css
 */
class Badge extends UXComponent
{
    protected string $variant = 'default';
    protected string $size = 'md';
    protected bool $pill = false;
    protected bool $dot = false;
    protected string $text = '';

    public function __construct(mixed $text = '')
    {
        parent::__construct();
        $this->text = (string)$text;
    }

    public static function make(mixed $text = ''): static
    {
        return new static($text);
    }

    /**
     * 设置颜色变体
     * @param string $variant 变体名：default/primary/success/warning/danger/info
     * @return static
     * @ux-example Badge::make('99')->variant('primary')
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 默认变体
     * @return static
     * @ux-example Badge::make('99')->default()
     */
    public function default(): static
    {
        return $this->variant('default');
    }

    /**
     * 主色变体
     * @return static
     * @ux-example Badge::make('99')->primary()
     */
    public function primary(): static
    {
        return $this->variant('primary');
    }

    /**
     * 成功变体
     * @return static
     * @ux-example Badge::make('99')->success()
     */
    public function success(): static
    {
        return $this->variant('success');
    }

    /**
     * 警告变体
     * @return static
     * @ux-example Badge::make('99')->warning()
     */
    public function warning(): static
    {
        return $this->variant('warning');
    }

    /**
     * 危险变体
     * @return static
     * @ux-example Badge::make('99')->danger()
     */
    public function danger(): static
    {
        return $this->variant('danger');
    }

    /**
     * 信息变体
     * @return static
     * @ux-example Badge::make('99')->info()
     */
    public function info(): static
    {
        return $this->variant('info');
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-example Badge::make('99')->size('sm')
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 小尺寸
     * @return static
     * @ux-example Badge::make('99')->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 中等尺寸
     * @return static
     * @ux-example Badge::make('99')->md()
     */
    public function md(): static
    {
        return $this->size('md');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example Badge::make('99')->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 胶囊形状
     * @param bool $pill 是否胶囊
     * @return static
     * @ux-example Badge::make('99')->pill()
     * @ux-default true
     */
    public function pill(bool $pill = true): static
    {
        $this->pill = $pill;
        return $this;
    }

    /**
     * 圆点模式
     * @param bool $dot 是否圆点
     * @return static
     * @ux-example Badge::make()->dot()
     * @ux-default true
     */
    public function dot(bool $dot = true): static
    {
        $this->dot = $dot;
        return $this;
    }

    /**
     * 设置显示文字
     * @param string $text 文字内容
     * @return static
     * @ux-example Badge::make()->text('99+')
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-badge');
        $el->class("ux-badge-{$this->variant}");
        $el->class("ux-badge-{$this->size}");

        if ($this->pill) {
            $el->class('ux-badge-pill');
        }

        if ($this->dot) {
            $el->class('ux-badge-dot');
            $el->child(Element::make('span')->class('ux-badge-dot-indicator'));
        }

        $el->text($this->text);
        $this->appendChildren($el);

        return $el;
    }
}
