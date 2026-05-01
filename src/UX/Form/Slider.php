<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

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

    public function min(float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(float $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function value(float $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function step(float $step): static
    {
        $this->step = $step;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function vertical(bool $vertical = true): static
    {
        $this->vertical = $vertical;
        return $this;
    }

    public function range(bool $range = true): static
    {
        $this->range = $range;
        return $this;
    }

    public function rangeValue(float $start, float $end): static
    {
        $this->rangeValue = [$start, $end];
        return $this;
    }

    public function showTooltip(bool $show = true): static
    {
        $this->showTooltip = $show;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

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
