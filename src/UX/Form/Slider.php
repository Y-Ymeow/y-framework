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
    protected static ?string $componentName = 'slider';

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

    protected function init(): void
    {
        $this->registerJs('slider', '
            const Slider = {
                activeSlider: null,
                activeHandle: null,
                startX: 0,
                startY: 0,
                startValue: 0,
                init() {
                    // 初始化所有滑块：根据默认值设置 handle 位置
                    document.querySelectorAll(".ux-slider").forEach(slider => {
                        const value = slider.dataset.sliderValue;
                        if (value !== undefined && value !== "") {
                            if (slider.classList.contains("ux-slider-range")) {
                                try {
                                    const values = JSON.parse(value);
                                    const handles = slider.querySelectorAll(".ux-slider-handle");
                                    values.forEach((val, i) => {
                                        if (handles[i]) this.setValue(slider, handles[i], val);
                                    });
                                } catch (e) {}
                            } else {
                                const handle = slider.querySelector(".ux-slider-handle");
                                if (handle) this.setValue(slider, handle, parseFloat(value) || 0);
                            }
                        }
                    });

                    document.addEventListener("mousedown", (e) => this.handleStart(e));
                    document.addEventListener("touchstart", (e) => this.handleStart(e), { passive: false });
                    document.addEventListener("mousemove", (e) => this.handleMove(e));
                    document.addEventListener("touchmove", (e) => this.handleMove(e), { passive: false });
                    document.addEventListener("mouseup", () => this.handleEnd());
                    document.addEventListener("touchend", () => this.handleEnd());
                    document.addEventListener("click", (e) => {
                        const track = e.target.closest(".ux-slider-track");
                        if (track && !e.target.closest(".ux-slider-handle")) {
                            const slider = track.closest(".ux-slider");
                            if (slider && !slider.classList.contains("ux-slider-disabled")) {
                                this.jumpToPosition(slider, e);
                            }
                        }
                    });
                },
                handleStart(e) {
                    const handle = e.target.closest(".ux-slider-handle");
                    if (!handle) return;
                    const slider = handle.closest(".ux-slider");
                    if (!slider || slider.classList.contains("ux-slider-disabled")) return;
                    e.preventDefault();
                    this.activeSlider = slider;
                    this.activeHandle = handle;
                    handle.classList.add("dragging");
                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    this.startX = clientX;
                    this.startY = clientY;
                    this.startValue = this.getHandleValue(slider, handle);
                },
                handleMove(e) {
                    if (!this.activeSlider || !this.activeHandle) return;
                    e.preventDefault();
                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    const isVertical = this.activeSlider.classList.contains("ux-slider-vertical");
                    const track = this.activeSlider.querySelector(".ux-slider-track");
                    const rect = track.getBoundingClientRect();
                    let percent = isVertical ? (rect.bottom - clientY) / rect.height : (clientX - rect.left) / rect.width;
                    percent = Math.max(0, Math.min(1, percent));
                    const min = parseFloat(this.activeSlider.dataset.sliderMin) || 0;
                    const max = parseFloat(this.activeSlider.dataset.sliderMax) || 100;
                    const step = parseFloat(this.activeSlider.dataset.sliderStep) || 1;
                    let value = min + percent * (max - min);
                    value = Math.round(value / step) * step;
                    value = Math.max(min, Math.min(max, value));
                    this.setValue(this.activeSlider, this.activeHandle, value);
                },
                handleEnd() {
                    if (!this.activeSlider || !this.activeHandle) return;
                    this.activeHandle.classList.remove("dragging");
                    const value = this.getValue(this.activeSlider);
                    this.activeSlider.dispatchEvent(new CustomEvent("ux:change", { detail: { value }, bubbles: true }));
                    this.activeSlider = null;
                    this.activeHandle = null;
                },
                jumpToPosition(slider, e) {
                    const track = slider.querySelector(".ux-slider-track");
                    const rect = track.getBoundingClientRect();
                    const isVertical = slider.classList.contains("ux-slider-vertical");
                    const isRange = slider.classList.contains("ux-slider-range");
                    let percent = isVertical ? (rect.bottom - e.clientY) / rect.height : (e.clientX - rect.left) / rect.width;
                    percent = Math.max(0, Math.min(1, percent));
                    const min = parseFloat(slider.dataset.sliderMin) || 0;
                    const max = parseFloat(slider.dataset.sliderMax) || 100;
                    const step = parseFloat(slider.dataset.sliderStep) || 1;
                    let value = min + percent * (max - min);
                    value = Math.round(value / step) * step;
                    value = Math.max(min, Math.min(max, value));
                    if (isRange) {
                        const handles = slider.querySelectorAll(".ux-slider-handle");
                        let closestHandle = null;
                        let closestDistance = Infinity;
                        handles.forEach(handle => {
                            const handleValue = this.getHandleValue(slider, handle);
                            const distance = Math.abs(handleValue - value);
                            if (distance < closestDistance) {
                                closestDistance = distance;
                                closestHandle = handle;
                            }
                        });
                        if (closestHandle) this.setValue(slider, closestHandle, value);
                    } else {
                        const handle = slider.querySelector(".ux-slider-handle");
                        if (handle) this.setValue(slider, handle, value);
                    }
                },
                setValue(slider, handle, value) {
                    const min = parseFloat(slider.dataset.sliderMin) || 0;
                    const max = parseFloat(slider.dataset.sliderMax) || 100;
                    const percent = ((value - min) / (max - min)) * 100;
                    const isVertical = slider.classList.contains("ux-slider-vertical");
                    if (isVertical) handle.style.bottom = `${percent}%`;
                    else handle.style.left = `${percent}%`;
                    const tooltip = handle.querySelector(".ux-slider-tooltip");
                    if (tooltip) tooltip.textContent = value;
                    this.updateProgress(slider);
                    if (slider.classList.contains("ux-slider-range")) {
                        const handles = Array.from(slider.querySelectorAll(".ux-slider-handle"));
                        const values = handles.map(h => this.getHandleValue(slider, h));
                        slider.dataset.sliderValue = JSON.stringify(values);
                    } else {
                        slider.dataset.sliderValue = value;
                    }
                },
                updateProgress(slider) {
                    const progress = slider.querySelector(".ux-slider-progress");
                    if (!progress) return;
                    const isRange = slider.classList.contains("ux-slider-range");
                    const isVertical = slider.classList.contains("ux-slider-vertical");
                    if (isRange) {
                        const handles = Array.from(slider.querySelectorAll(".ux-slider-handle"));
                        const values = handles.map(h => this.getHandleValue(slider, h));
                        const minVal = Math.min(...values);
                        const maxVal = Math.max(...values);
                        const min = parseFloat(slider.dataset.sliderMin) || 0;
                        const max = parseFloat(slider.dataset.sliderMax) || 100;
                        const startPercent = ((minVal - min) / (max - min)) * 100;
                        const endPercent = ((maxVal - min) / (max - min)) * 100;
                        if (isVertical) {
                            progress.style.bottom = `${startPercent}%`;
                            progress.style.height = `${endPercent - startPercent}%`;
                        } else {
                            progress.style.left = `${startPercent}%`;
                            progress.style.width = `${endPercent - startPercent}%`;
                        }
                    } else {
                        const handle = slider.querySelector(".ux-slider-handle");
                        if (handle) {
                            const value = this.getHandleValue(slider, handle);
                            const min = parseFloat(slider.dataset.sliderMin) || 0;
                            const max = parseFloat(slider.dataset.sliderMax) || 100;
                            const percent = ((value - min) / (max - min)) * 100;
                            if (isVertical) progress.style.height = `${percent}%`;
                            else progress.style.width = `${percent}%`;
                        }
                    }
                },
                getHandleValue(slider, handle) {
                    const min = parseFloat(slider.dataset.sliderMin) || 0;
                    const max = parseFloat(slider.dataset.sliderMax) || 100;
                    const isVertical = slider.classList.contains("ux-slider-vertical");
                    const style = handle.style;
                    const percentStr = isVertical ? style.bottom : style.left;
                    const percent = parseFloat(percentStr) || 0;
                    return min + (percent / 100) * (max - min);
                },
                getValue(slider) {
                    if (slider.classList.contains("ux-slider-range")) {
                        const handles = Array.from(slider.querySelectorAll(".ux-slider-handle"));
                        return handles.map(h => this.getHandleValue(slider, h));
                    }
                    return parseFloat(slider.dataset.sliderValue) || 0;
                },
                setSliderValue(id, value) {
                    const slider = document.querySelector(`#${id}.ux-slider`);
                    if (!slider) return;
                    if (Array.isArray(value) && slider.classList.contains("ux-slider-range")) {
                        const handles = slider.querySelectorAll(".ux-slider-handle");
                        value.forEach((val, i) => { if (handles[i]) this.setValue(slider, handles[i], val); });
                    } else {
                        const handle = slider.querySelector(".ux-slider-handle");
                        if (handle) this.setValue(slider, handle, value);
                    }
                }
            };
            return Slider;
        ');
    }

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
