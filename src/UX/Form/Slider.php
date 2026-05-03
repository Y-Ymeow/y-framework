<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 滑块
 *
 * 用于数值范围选择，支持最小/最大值、步长、禁用、垂直/水平、范围选择、提示框。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Slider::make()->min(0)->max(100)->value(50)
 * @ux-example Slider::make()->range()->rangeValue(20, 80)->vertical()
 * @ux-example Slider::make()->value(75)->step(5)->format('%.0f%%')->showTooltip()
 * @ux-js-component slider.js
 * @ux-css slider.css
 */
class Slider extends UXComponent
{
    protected float $min = 0;
    protected float $max = 100;
    protected float $value = 0;
    protected float $step = 1;
    protected bool $disabled = false;
    protected bool $vertical = false;
    protected bool $range = false;
    protected ?array $rangeValue = null;
    protected bool $showTooltip = true;
    protected ?string $action = null;
    protected ?string $format = null;

    /**
     * 设置最小值
     * @param float $min 最小值
     * @return static
     * @ux-example Slider::make()->min(0)
     * @ux-default 0
     */
    public function min(float $min): static
    {
        $this->min = $min;
        return $this;
    }

    /**
     * 设置最大值
     * @param float $max 最大值
     * @return static
     * @ux-example Slider::make()->max(100)
     * @ux-default 100
     */
    public function max(float $max): static
    {
        $this->max = $max;
        return $this;
    }

    /**
     * 设置默认值
     * @param float $value 默认值
     * @return static
     * @ux-example Slider::make()->value(50)
     * @ux-default 0
     */
    public function value(float $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置步长
     * @param float $step 步长
     * @return static
     * @ux-example Slider::make()->step(5)
     * @ux-default 1
     */
    public function step(float $step): static
    {
        $this->step = $step;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example Slider::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置为垂直方向
     * @param bool $vertical 是否垂直
     * @return static
     * @ux-example Slider::make()->vertical()
     * @ux-default true
     */
    public function vertical(bool $vertical = true): static
    {
        $this->vertical = $vertical;
        return $this;
    }

    /**
     * 启用范围选择（双滑块）
     * @param bool $range 是否范围选择
     * @return static
     * @ux-example Slider::make()->range()
     * @ux-default true
     */
    public function range(bool $range = true): static
    {
        $this->range = $range;
        return $this;
    }

    /**
     * 设置范围值
     * @param float $start 起始值
     * @param float $end 结束值
     * @return static
     * @ux-example Slider::make()->rangeValue(20, 80)
     */
    public function rangeValue(float $start, float $end): static
    {
        $this->rangeValue = [$start, $end];
        return $this;
    }

    /**
     * 显示提示框
     * @param bool $show 是否显示
     * @return static
     * @ux-example Slider::make()->showTooltip(false)
     * @ux-default true
     */
    public function showTooltip(bool $show = true): static
    {
        $this->showTooltip = $show;
        return $this;
    }

    /**
     * 设置 LiveAction（滑块变化时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example Slider::make()->action('updateSlider')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置数值格式化
     * @param string $format sprintf 格式字符串
     * @return static
     * @ux-example Slider::make()->format('%.0f%%')
     */
    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-slider');
        if ($this->vertical) {
            $el->class('ux-slider-vertical');
        }
        if ($this->range) {
            $el->class('ux-slider-range');
        }
        if ($this->disabled) {
            $el->class('ux-slider-disabled');
        }

        $el->data('slider-min', (string)$this->min);
        $el->data('slider-max', (string)$this->max);
        $el->data('slider-step', (string)$this->step);

        if ($this->range && $this->rangeValue) {
            $el->data('slider-value', json_encode($this->rangeValue));
        } else {
            $el->data('slider-value', (string)$this->value);
        }

        if ($this->action) {
            $el->data('slider-action', $this->action);
        }

        if ($this->format) {
            $el->data('slider-format', $this->format);
        }

        // 轨道
        $trackEl = Element::make('div')->class('ux-slider-track');

        // 进度条
        $progressEl = Element::make('div')->class('ux-slider-progress');
        if ($this->range && $this->rangeValue) {
            $startPercent = (($this->rangeValue[0] - $this->min) / ($this->max - $this->min)) * 100;
            $endPercent = (($this->rangeValue[1] - $this->min) / ($this->max - $this->min)) * 100;
            $progressEl->style("left: {$startPercent}%; width: " . ($endPercent - $startPercent) . '%');
        } else {
            $percent = (($this->value - $this->min) / ($this->max - $this->min)) * 100;
            $progressEl->style("width: {$percent}%");
        }
        $trackEl->child($progressEl);

        // 滑块手柄
        if ($this->range && $this->rangeValue) {
            foreach ($this->rangeValue as $i => $val) {
                $percent = (($val - $this->min) / ($this->max - $this->min)) * 100;
                $handleEl = Element::make('div')
                    ->class('ux-slider-handle')
                    ->class("ux-slider-handle-{$i}")
                    ->data('handle-index', (string)$i);
                if (!$this->vertical) {
                    $handleEl->style("left: {$percent}%");
                } else {
                    $handleEl->style("bottom: {$percent}%");
                }

                if ($this->showTooltip) {
                    $tooltipEl = Element::make('div')
                        ->class('ux-slider-tooltip')
                        ->text($this->format ? sprintf($this->format, $val) : (string)$val);
                    $handleEl->child($tooltipEl);
                }

                $trackEl->child($handleEl);
            }
        } else {
            $percent = (($this->value - $this->min) / ($this->max - $this->min)) * 100;
            $handleEl = Element::make('div')
                ->class('ux-slider-handle')
                ->data('handle-index', '0');
            if (!$this->vertical) {
                $handleEl->style("left: {$percent}%");
            } else {
                $handleEl->style("bottom: {$percent}%");
            }

            if ($this->showTooltip) {
                $tooltipEl = Element::make('div')
                    ->class('ux-slider-tooltip')
                    ->text($this->format ? sprintf($this->format, $this->value) : (string)$this->value);
                $handleEl->child($tooltipEl);
            }

            $trackEl->child($handleEl);
        }

        $el->child($trackEl);

        // Live 桥接隐藏 input
        $sliderValue = $this->range && $this->rangeValue ? json_encode($this->rangeValue) : (string)$this->value;
        $liveInput = $this->createLiveModelInput($sliderValue);
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
