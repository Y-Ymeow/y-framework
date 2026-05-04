<?php

declare(strict_types=1);

namespace Framework\UX\Overlay;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 提示框
 *
 * 用于鼠标悬停显示提示信息，支持自定义内容、位置、触发方式。
 *
 * @ux-category Overlay
 * @ux-since 1.0.0
 * @ux-example Tooltip::make()->content('这是一个提示')->trigger(Button::make()->label('悬停我'))
 * @ux-example Tooltip::make()->content($view)->position('top')->trigger('更多')
 * @ux-js-component tooltip.js
 * @ux-css tooltip.css
 */
class Tooltip extends UXComponent
{
    protected static ?string $componentName = 'tooltip';

    protected string $content = '';
    protected string $placement = 'top';
    protected ?string $trigger = null;
    protected bool $arrow = true;
    protected int $delay = 0;
    protected ?int $maxWidth = null;

    protected function init(): void
    {
        $this->registerJs('tooltip', '
            const Tooltip = {
                tooltips: new Map(),
                init() {
                    document.addEventListener("mouseenter", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest("[data-tooltip]");
                        if (wrapper && wrapper.dataset.tooltipTrigger !== "click") this.show(wrapper);
                    }, true);
                    document.addEventListener("mouseleave", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest("[data-tooltip]");
                        if (wrapper && wrapper.dataset.tooltipTrigger !== "click") this.hide(wrapper);
                    }, true);
                    document.addEventListener("focus", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest("[data-tooltip]");
                        if (wrapper && wrapper.dataset.tooltipTrigger === "focus") this.show(wrapper);
                    }, true);
                    document.addEventListener("blur", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest("[data-tooltip]");
                        if (wrapper && wrapper.dataset.tooltipTrigger === "focus") this.hide(wrapper);
                    }, true);
                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest("[data-tooltip]");
                        if (wrapper && wrapper.dataset.tooltipTrigger === "click") {
                            e.preventDefault();
                            this.toggle(wrapper);
                        } else {
                            this.hideAllClickTooltips();
                        }
                    });
                },
                show(wrapper) {
                    const content = wrapper.dataset.tooltip;
                    if (!content) return;
                    const delay = parseInt(wrapper.dataset.tooltipDelay) || 0;
                    setTimeout(() => {
                        let tooltip = this.tooltips.get(wrapper);
                        if (!tooltip) {
                            tooltip = this.createTooltip(wrapper);
                            this.tooltips.set(wrapper, tooltip);
                        }
                        tooltip.classList.add("show");
                    }, delay);
                },
                hide(wrapper) {
                    const tooltip = this.tooltips.get(wrapper);
                    if (tooltip) tooltip.classList.remove("show");
                },
                toggle(wrapper) {
                    const tooltip = this.tooltips.get(wrapper);
                    if (tooltip && tooltip.classList.contains("show")) this.hide(wrapper);
                    else this.show(wrapper);
                },
                createTooltip(wrapper) {
                    const content = wrapper.dataset.tooltip;
                    const placement = wrapper.dataset.tooltipPlacement || "top";
                    const showArrow = wrapper.dataset.tooltipArrow !== "false";
                    const maxWidth = wrapper.dataset.tooltipMaxWidth;
                    const tooltip = document.createElement("div");
                    tooltip.className = "ux-tooltip";
                    tooltip.textContent = content;
                    tooltip.dataset.placement = placement;
                    tooltip.dataset.arrow = showArrow;
                    if (maxWidth) tooltip.style.setProperty("--tooltip-max-width", `${maxWidth}px`);
                    wrapper.style.position = "relative";
                    wrapper.appendChild(tooltip);
                    return tooltip;
                },
                hideAllClickTooltips() {
                    this.tooltips.forEach((tooltip, wrapper) => {
                        if (wrapper.dataset.tooltipTrigger === "click") tooltip.classList.remove("show");
                    });
                }
            };
            return Tooltip;
        ');
    }

    /**
     * 设置提示内容
     * @param string $content 提示文字
     * @return static
     */
    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置提示位置
     * @param string $placement 位置：top/bottom/left/right
     * @return static
     * @ux-default 'top'
     */
    public function placement(string $placement): static
    {
        $this->placement = $placement;
        return $this;
    }

    /**
     * 顶部提示
     * @return static
     */
    public function top(): static
    {
        return $this->placement('top');
    }

    /**
     * 底部提示
     * @return static
     */
    public function bottom(): static
    {
        return $this->placement('bottom');
    }

    /**
     * 左侧提示
     * @return static
     */
    public function left(): static
    {
        return $this->placement('left');
    }

    /**
     * 右侧提示
     * @return static
     */
    public function right(): static
    {
        return $this->placement('right');
    }

    /**
     * 设置触发方式
     * @param string $trigger 触发方式：hover/click/focus
     * @return static
     */
    public function trigger(string $trigger): static
    {
        $this->trigger = $trigger;
        return $this;
    }

    /**
     * 悬停触发
     * @return static
     */
    public function hover(): static
    {
        return $this->trigger('hover');
    }

    /**
     * 点击触发
     * @return static
     */
    public function click(): static
    {
        return $this->trigger('click');
    }

    /**
     * 聚焦触发
     * @return static
     */
    public function focus(): static
    {
        return $this->trigger('focus');
    }

    /**
     * 是否显示箭头
     * @param bool $arrow 是否显示
     * @return static
     * @ux-default true
     */
    public function arrow(bool $arrow = true): static
    {
        $this->arrow = $arrow;
        return $this;
    }

    /**
     * 设置显示延迟（毫秒）
     * @param int $delay 延迟时间
     * @return static
     * @ux-default 0
     */
    public function delay(int $delay): static
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * 设置最大宽度
     * @param int $width 最大宽度（像素）
     * @return static
     */
    public function maxWidth(int $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-tooltip-wrapper');
        $el->data('tooltip', $this->content);
        $el->data('tooltip-placement', $this->placement);

        if ($this->trigger) {
            $el->data('tooltip-trigger', $this->trigger);
        }

        if (!$this->arrow) {
            $el->data('tooltip-arrow', 'false');
        }

        if ($this->delay > 0) {
            $el->data('tooltip-delay', (string)$this->delay);
        }

        if ($this->maxWidth) {
            $el->data('tooltip-max-width', (string)$this->maxWidth);
        }

        // 添加子元素
        $this->appendChildren($el);

        return $el;
    }
}
