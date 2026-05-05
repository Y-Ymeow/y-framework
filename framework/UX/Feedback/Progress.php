<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 进度条
 *
 * 用于显示任务进度，支持多种颜色变体、尺寸、条纹、动画、标签显示。
 *
 * @ux-category Feedback
 * @ux-since 1.0.0
 * @ux-example Progress::make()->value(75)->primary()
 * @ux-example Progress::make()->value(50)->success()->striped()->animated()
 * @ux-example Progress::make()->value(100)->showLabel()
 * @ux-js-component —
 * @ux-css progress.css
 */
class Progress extends UXComponent
{
    protected int $value = 0;
    protected int $max = 100;
    protected string $variant = 'primary';
    protected bool $showLabel = false;
    protected bool $striped = false;
    protected bool $animated = false;
    protected string $size = 'md';

    /**
     * 设置进度值
     * @param int $value 进度值（0-max）
     * @return static
     * @ux-example Progress::make()->value(75)
     * @ux-default 0
     */
    public function value(int $value): static
    {
        $this->value = max(0, min($value, $this->max));
        return $this;
    }

    /**
     * 设置最大值
     * @param int $max 最大值
     * @return static
     * @ux-example Progress::make()->max(200)
     * @ux-default 100
     */
    public function max(int $max): static
    {
        $this->max = max(1, $max);
        return $this;
    }

    /**
     * 设置颜色变体
     * @param string $variant 变体名：primary/success/warning/danger/info
     * @return static
     * @ux-example Progress::make()->variant('success')
     * @ux-default 'primary'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 主色变体
     * @return static
     * @ux-example Progress::make()->primary()
     */
    public function primary(): static
    {
        return $this->variant('primary');
    }

    /**
     * 成功变体
     * @return static
     * @ux-example Progress::make()->success()
     */
    public function success(): static
    {
        return $this->variant('success');
    }

    /**
     * 警告变体
     * @return static
     * @ux-example Progress::make()->warning()
     */
    public function warning(): static
    {
        return $this->variant('warning');
    }

    /**
     * 危险变体
     * @return static
     * @ux-example Progress::make()->danger()
     */
    public function danger(): static
    {
        return $this->variant('danger');
    }

    /**
     * 信息变体
     * @return static
     * @ux-example Progress::make()->info()
     */
    public function info(): static
    {
        return $this->variant('info');
    }

    /**
     * 显示百分比标签
     * @param bool $show 是否显示
     * @return static
     * @ux-example Progress::make()->showLabel()
     * @ux-default true
     */
    public function showLabel(bool $show = true): static
    {
        $this->showLabel = $show;
        return $this;
    }

    /**
     * 启用条纹样式
     * @param bool $striped 是否条纹
     * @return static
     * @ux-example Progress::make()->striped()
     * @ux-default true
     */
    public function striped(bool $striped = true): static
    {
        $this->striped = $striped;
        return $this;
    }

    /**
     * 启用条纹动画
     * @param bool $animated 是否动画
     * @return static
     * @ux-example Progress::make()->animated()
     * @ux-default true
     */
    public function animated(bool $animated = true): static
    {
        $this->animated = $animated;
        return $this;
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-example Progress::make()->size('lg')
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
     * @ux-example Progress::make()->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example Progress::make()->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-progress');
        $el->class("ux-progress-{$this->size}");
        $el->attr('role', 'progressbar');
        $el->attr('aria-valuenow', (string) $this->value);
        $el->attr('aria-valuemin', '0');
        $el->attr('aria-valuemax', (string) $this->max);

        $percentage = round(($this->value / $this->max) * 100);

        $barEl = Element::make('div')
            ->class('ux-progress-bar')
            ->class("ux-progress-bar-{$this->variant}")
            ->style("width: {$percentage}%");

        if ($this->striped) {
            $barEl->class('ux-progress-bar-striped');
        }
        if ($this->animated) {
            $barEl->class('ux-progress-bar-animated');
        }

        if ($this->showLabel) {
            $barEl->child(
                Element::make('span')
                    ->class('ux-progress-label')
                    ->text("{$percentage}%")
            );
        }

        $el->child($barEl);

        return $el;
    }
}
