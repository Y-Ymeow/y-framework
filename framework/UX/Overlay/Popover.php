<?php

declare(strict_types=1);

namespace Framework\UX\Overlay;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 气泡卡片
 *
 * 用于点击/悬停显示浮层内容，支持标题、内容、位置、触发方式、箭头、最大宽度。
 *
 * @ux-category Overlay
 * @ux-since 1.0.0
 * @ux-example Popover::make()->title('标题')->content('内容')->trigger('click')->placement('bottom')
 * @ux-example Popover::make()->content($view)->hover()->arrow(false)->maxWidth(300)
 * @ux-js-component popover.js
 * @ux-css popover.css
 */
class Popover extends UXComponent
{
    protected static ?string $componentName = 'popover';

    protected ?string $title = null;
    protected ?string $content = null;
    protected string $placement = 'top';
    protected string $trigger = 'click';
    protected bool $arrow = true;
    protected ?int $maxWidth = null;
    protected bool $open = false;

    protected function init(): void
    {
        $this->registerJs('popover', '
            const Popover = {
                wrapperMap: new Map(),
                init() {
                    // 初始化所有 popover
                    document.querySelectorAll(".ux-popover-wrapper").forEach(wrapper => {
                        this.wrapperMap.set(wrapper, { popover: null, open: false });
                    });

                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest(".ux-popover-wrapper");
                        if (wrapper && wrapper.dataset.popoverTrigger === "click") {
                            e.preventDefault();
                            this.toggle(wrapper);
                        } else if (!e.target.closest(".ux-popover")) {
                            this.hideAll();
                        }
                    });
                    document.addEventListener("mouseenter", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest(".ux-popover-wrapper");
                        if (wrapper && wrapper.dataset.popoverTrigger === "hover") this.show(wrapper);
                    }, true);
                    document.addEventListener("mouseleave", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest(".ux-popover-wrapper");
                        if (wrapper && wrapper.dataset.popoverTrigger === "hover") this.hide(wrapper);
                    }, true);
                    document.addEventListener("focus", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest(".ux-popover-wrapper");
                        if (wrapper && wrapper.dataset.popoverTrigger === "focus") this.show(wrapper);
                    }, true);
                    document.addEventListener("blur", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const wrapper = e.target.closest(".ux-popover-wrapper");
                        if (wrapper && wrapper.dataset.popoverTrigger === "focus") this.hide(wrapper);
                    }, true);
                },
                toggle(wrapper) {
                    const state = this.wrapperMap.get(wrapper);
                    if (!state) return;
                    if (state.open) this.hide(wrapper);
                    else this.show(wrapper);
                },
                show(wrapper) {
                    const state = this.wrapperMap.get(wrapper);
                    if (!state) return;
                    this.hideAll();

                    let popover = state.popover;
                    if (!popover) {
                        popover = this.createPopover(wrapper);
                        if (!popover) return;
                        state.popover = popover;
                    }

                    this.position(wrapper, popover);
                    popover.classList.add("show");
                    state.open = true;
                },
                hide(wrapper) {
                    const state = this.wrapperMap.get(wrapper);
                    if (!state || !state.popover) return;
                    state.popover.classList.remove("show");
                    state.open = false;
                },
                hideAll() {
                    document.querySelectorAll(".ux-popover.show").forEach(popover => popover.classList.remove("show"));
                    this.wrapperMap.forEach(state => { state.open = false; });
                },
                createPopover(wrapper) {
                    const title = wrapper.dataset.popoverTitle || "";
                    const content = wrapper.dataset.popoverContent || "";
                    if (!title && !content) return null;

                    const placement = wrapper.dataset.popoverPlacement || "top";
                    const showArrow = wrapper.dataset.popoverArrow !== "false";

                    const popover = document.createElement("div");
                    popover.className = "ux-popover";
                    popover.dataset.placement = placement;

                    let html = "";
                    if (showArrow) html += `<div class="ux-popover-arrow"></div>`;
                    if (title) html += `<div class="ux-popover-title">${this.escapeHtml(title)}</div>`;
                    if (content) html += `<div class="ux-popover-content">${this.escapeHtml(content)}</div>`;
                    popover.innerHTML = html;

                    wrapper.appendChild(popover);
                    return popover;
                },
                position(wrapper, popover) {
                    const placement = wrapper.dataset.popoverPlacement || "top";
                    const rect = wrapper.getBoundingClientRect();
                    
                    popover.style.visibility = "hidden";
                    popover.style.display = "block";
                    const popoverRect = popover.getBoundingClientRect();
                    popover.style.display = "";
                    popover.style.visibility = "";

                    const gap = 8;
                    let top, left;
                    
                    if (placement === "top") {
                        top = rect.top - popoverRect.height - gap;
                        left = rect.left + (rect.width - popoverRect.width) / 2;
                    } else if (placement === "bottom") {
                        top = rect.bottom + gap;
                        left = rect.left + (rect.width - popoverRect.width) / 2;
                    } else if (placement === "left") {
                        top = rect.top + (rect.height - popoverRect.height) / 2;
                        left = rect.left - popoverRect.width - gap;
                    } else if (placement === "right") {
                        top = rect.top + (rect.height - popoverRect.height) / 2;
                        left = rect.right + gap;
                    }
                    
                    const vw = window.innerWidth;
                    const vh = window.innerHeight;
                    
                    left = Math.max(4, Math.min(left, vw - popoverRect.width - 4));
                    top = Math.max(4, Math.min(top, vh - popoverRect.height - 4));
                    
                    popover.style.position = "fixed";
                    popover.style.top = `${top}px`;
                    popover.style.left = `${left}px`;
                    popover.style.zIndex = "1050";
                    popover.style.margin = "0";
                    popover.style.bottom = "auto";
                    popover.style.right = "auto";
                    popover.style.transform = "none";
                },
                escapeHtml(text) {
                    const div = document.createElement("div");
                    div.textContent = text;
                    return div.innerHTML;
                }
            };
            return Popover;
        ');

        $this->registerCss(<<<'CSS'
.ux-popover-wrapper {
    position: relative;
    display: inline-block;
}
.ux-popover {
    position: fixed;
    z-index: 1050;
    min-width: 10rem;
    max-width: 20rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transform: scale(0.95);
    transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
}
.ux-popover.show {
    opacity: 1;
    visibility: visible;
    transform: scale(1);
}
.ux-popover-arrow {
    position: absolute;
    width: 0.5rem;
    height: 0.5rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    transform: rotate(45deg);
}
.ux-popover[data-placement="top"] .ux-popover-arrow {
    bottom: -0.3rem;
    border-top: none;
    border-left: none;
}
.ux-popover[data-placement="bottom"] .ux-popover-arrow {
    top: -0.3rem;
    border-bottom: none;
    border-right: none;
}
.ux-popover[data-placement="left"] .ux-popover-arrow {
    right: -0.3rem;
    border-bottom: none;
    border-left: none;
}
.ux-popover[data-placement="right"] .ux-popover-arrow {
    left: -0.3rem;
    border-top: none;
    border-right: none;
}
.ux-popover-title {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.ux-popover-content {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.5;
}
CSS
        );
    }

    /**
     * 设置气泡标题
     * @param string $title 标题文字
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置气泡内容
     * @param string $content 内容文字
     * @return static
     */
    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置气泡位置
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
     * 顶部气泡
     * @return static
     */
    public function top(): static
    {
        return $this->placement('top');
    }

    /**
     * 底部气泡
     * @return static
     */
    public function bottom(): static
    {
        return $this->placement('bottom');
    }

    /**
     * 左侧气泡
     * @return static
     */
    public function left(): static
    {
        return $this->placement('left');
    }

    /**
     * 右侧气泡
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
     * @ux-default 'click'
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
     * 设置打开状态
     * @param bool $open 是否打开
     * @return static
     * @ux-default false
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-popover-wrapper');
        $el->data('popover-placement', $this->placement);
        $el->data('popover-trigger', $this->trigger);

        if (!$this->arrow) {
            $el->data('popover-arrow', 'false');
        }

        if ($this->maxWidth) {
            $el->data('popover-max-width', (string)$this->maxWidth);
        }

        if ($this->open) {
            $el->data('popover-open', 'true');
        }

        // 添加子元素（触发器）
        $this->appendChildren($el);

        // Popover 内容（通过 JS 动态创建）
        $el->data('popover-title', $this->title ?? '');
        $el->data('popover-content', $this->content ?? '');

        return $el;
    }
}
