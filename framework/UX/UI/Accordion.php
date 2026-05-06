<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 手风琴
 *
 * 用于展示可折叠的内容面板组，支持多面板、标题、内容、展开/折叠控制。
 *
 * @ux-category UI
 * @ux-since 1.0.0
 * @ux-example Accordion::make()->panel('面板1', '内容1')->panel('面板2', '内容2')
 * @ux-example Accordion::make()->panel('标题1', $view1)->panel('标题2', $view2)->allowMultiple()
 * @ux-js-component accordion.js
 * @ux-css accordion.css
 */
class Accordion extends UXComponent
{
    protected static ?string $componentName = 'accordion';

    protected array $items = [];
    protected bool $multiple = false;
    protected string $variant = 'default';
    protected bool $dark = false;

    protected function init(): void
    {
        $this->registerJs('accordion', '
            const Accordion = {
                toggle(id, forceOpen = null) {
                    const item = typeof id === "string" ? document.getElementById(id) : id;
                    if (!item) return;
                    const accordion = item.closest(".ux-accordion");
                    const isOpen = forceOpen !== null ? !forceOpen : item.classList.contains("open");
                    if (accordion?.dataset.accordionMultiple !== "true" && !isOpen) {
                        accordion.querySelectorAll(".ux-accordion-item").forEach(i => {
                            i.classList.remove("open");
                            i.querySelector(".ux-accordion-collapse")?.classList.remove("show");
                        });
                    }
                    if (isOpen) {
                        item.classList.remove("open");
                        item.querySelector(".ux-accordion-collapse")?.classList.remove("show");
                    } else {
                        item.classList.add("open");
                        item.querySelector(".ux-accordion-collapse")?.classList.add("show");
                    }
                },
                init() {
                    // 初始化所有手风琴：确保展开状态与 class 一致
                    document.querySelectorAll(".ux-accordion").forEach(accordion => {
                        accordion.querySelectorAll(".ux-accordion-item").forEach(item => {
                            const isOpen = item.classList.contains("open");
                            const collapse = item.querySelector(".ux-accordion-collapse");
                            if (collapse) collapse.classList.toggle("show", isOpen);
                            const header = item.querySelector(".ux-accordion-header");
                            if (header) header.setAttribute("aria-expanded", String(isOpen));
                        });
                    });

                    document.addEventListener("click", (e) => {
                        const header = e.target.closest(".ux-accordion-header");
                        if (!header) return;
                        const item = header.closest(".ux-accordion-item");
                        if (!item) return;
                        const accordion = item.closest(".ux-accordion");
                        const isOpen = item.classList.contains("open");
                        
                        // 如果不是多开模式，先关闭其他项
                        if (accordion?.dataset.accordionMultiple !== "true" && !isOpen) {
                            accordion.querySelectorAll(".ux-accordion-item").forEach(i => {
                                if (i !== item) {
                                    i.classList.remove("open");
                                    i.querySelector(".ux-accordion-collapse")?.classList.remove("show");
                                    i.querySelector(".ux-accordion-header")?.setAttribute("aria-expanded", "false");
                                }
                            });
                        }
                        
                        // 切换当前项
                        if (isOpen) {
                            item.classList.remove("open");
                            item.querySelector(".ux-accordion-collapse")?.classList.remove("show");
                            header.setAttribute("aria-expanded", "false");
                        } else {
                            item.classList.add("open");
                            item.querySelector(".ux-accordion-collapse")?.classList.add("show");
                            header.setAttribute("aria-expanded", "true");
                        }
                    });
                }
            };
            return Accordion;
        ');

        $this->registerCss(<<<'CSS'
.ux-accordion {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}
.ux-accordion-item {
    border-bottom: 1px solid #e5e7eb;
}
.ux-accordion-item:last-child {
    border-bottom: none;
}
.ux-accordion-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.875rem 1rem;
    background: #fff;
    border: none;
    cursor: pointer;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #374151;
    text-align: left;
    transition: background-color 0.15s;
}
.ux-accordion-header:hover {
    background-color: #f9fafb;
}
.ux-accordion-icon {
    display: inline-flex;
    width: 1.25rem;
    height: 1.25rem;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
    flex-shrink: 0;
}
.ux-accordion-icon::after {
    content: "";
    border-right: 2px solid #9ca3af;
    border-bottom: 2px solid #9ca3af;
    width: 0.5rem;
    height: 0.5rem;
    transform: rotate(-45deg);
    transition: transform 0.2s ease;
}
.ux-accordion-item.open .ux-accordion-icon::after {
    transform: rotate(45deg);
}
.ux-accordion-collapse {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.25s ease;
}
.ux-accordion-collapse.show {
    max-height: 500px;
}
.ux-accordion-body {
    padding: 0.75rem 1rem 1rem;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.6;
}
.ux-accordion-dark {
    border-color: #374151;
}
.ux-accordion-dark .ux-accordion-header {
    background: #1f2937;
    color: #f9fafb;
}
.ux-accordion-dark .ux-accordion-header:hover {
    background: #374151;
}
.ux-accordion-dark .ux-accordion-body {
    background: #111827;
    color: #d1d5db;
}
.ux-accordion-dark .ux-accordion-item {
    border-bottom-color: #374151;
}
CSS
        );
    }

    /**
     * 添加面板项
     * @param mixed $title 标题（支持字符串或组件）
     * @param mixed $content 内容（支持字符串或组件）
     * @param string|null $id 面板 ID（自动生成）
     * @param bool $open 是否默认展开
     * @return static
     * @ux-example Accordion::make()->item('标题', '内容')->item('另一个', '内容2')
     */
    public function item(mixed $title, mixed $content, ?string $id = null, bool $open = false): static
    {
        $id = $id ?? 'accordion-item-' . count($this->items);
        $this->items[] = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'open' => $open,
        ];
        return $this;
    }

    /**
     * 允许多个面板同时展开
     * @param bool $multiple 是否允许多开
     * @return static
     * @ux-default false
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * 设置变体样式
     * @param string $variant 变体名
     * @return static
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 启用暗色主题
     * @param bool $dark 是否暗色
     * @return static
     * @ux-default false
     */
    public function dark(bool $dark = true): static
    {
        $this->dark = $dark;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-accordion');
        $el->class("ux-accordion-{$this->variant}");

        if ($this->dark) {
            $el->class('ux-accordion-dark');
        }

        if ($this->multiple) {
            $el->data('accordion-multiple', 'true');
        }

        foreach ($this->items as $item) {
            $isOpen = $item['open'];

            $itemEl = Element::make('div')
                ->class('ux-accordion-item')
                ->id($item['id']);
            if ($isOpen) {
                $itemEl->class('open');
            }

            // Header
            $headerEl = Element::make('button')
                ->class('ux-accordion-header')
                ->attr('type', 'button')
                ->attr('aria-expanded', $isOpen ? 'true' : 'false');

            if ($this->liveAction) {
                $headerEl->liveAction($this->liveAction, $this->liveEvent ?? 'click', ['id' => $item['id'], 'open' => !$isOpen]);
            }

            $titleEl = Element::make('span')->class('ux-accordion-title');
            $title = $item['title'];
            if (is_string($title)) {
                $titleEl->html($title);
            } else {
                $titleEl->child($this->resolveChild($title));
            }
            
            $headerEl->child($titleEl);
            $headerEl->child(Element::make('span')->class('ux-accordion-icon'));

            $itemEl->child($headerEl);

            // Content
            $collapseEl = Element::make('div')->class('ux-accordion-collapse');
            if ($isOpen) {
                $collapseEl->class('show');
            }

            $bodyEl = Element::make('div')->class('ux-accordion-body');
            $content = $item['content'];
            if (is_string($content)) {
                $bodyEl->html($content);
            } else {
                $bodyEl->child($this->resolveChild($content));
            }

            $collapseEl->child($bodyEl);
            $itemEl->child($collapseEl);

            $el->child($itemEl);
        }

        return $el;
    }
}
