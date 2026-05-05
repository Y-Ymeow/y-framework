<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 折叠面板
 *
 * 用于折叠/展开内容区块，支持标题、开关、禁用、图标、自定义操作。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Collapse::make()->title('面板1')->child('内容1')
 * @ux-example Collapse::make()->title('默认展开')->open()->child('内容')
 * @ux-example Collapse::make()->title('带图标')->icon('bi-chevron-down')->child('内容')
 * @ux-js-component collapse.js
 * @ux-css collapse.css
 */
class Collapse extends UXComponent
{
    protected static ?string $componentName = 'collapse';

    protected string $title = '';
    protected bool $open = false;
    protected bool $disabled = false;
    protected ?string $icon = null;
    protected ?string $action = null;

    protected function init(): void
    {
        $this->registerJs('collapse', '
            const Collapse = {
                init() {
                    document.addEventListener("click", (e) => {
                        const header = e.target.closest(".ux-collapse-header");
                        if (header) {
                            const collapse = header.closest(".ux-collapse");
                            if (collapse && !collapse.classList.contains("ux-collapse-disabled")) {
                                this.toggle(collapse);
                            }
                        }
                    });
                },
                toggle(collapse) {
                    const isOpen = collapse.classList.contains("ux-collapse-open");
                    isOpen ? this.close(collapse) : this.open(collapse);
                },
                open(collapse) {
                    collapse.classList.add("ux-collapse-open");
                    const action = collapse.dataset.collapseAction;
                    if (action && window.L) {
                        window.L.executeOperation({ op: "action", action: action, params: { open: true } });
                    }
                    collapse.dispatchEvent(new CustomEvent("collapse:open"));
                },
                close(collapse) {
                    collapse.classList.remove("ux-collapse-open");
                    const action = collapse.dataset.collapseAction;
                    if (action && window.L) {
                        window.L.executeOperation({ op: "action", action: action, params: { open: false } });
                    }
                    collapse.dispatchEvent(new CustomEvent("collapse:close"));
                },
                openById(id) {
                    const collapse = document.querySelector(`#${id}.ux-collapse`);
                    if (collapse) this.open(collapse);
                },
                closeById(id) {
                    const collapse = document.querySelector(`#${id}.ux-collapse`);
                    if (collapse) this.close(collapse);
                },
                toggleById(id) {
                    const collapse = document.querySelector(`#${id}.ux-collapse`);
                    if (collapse) this.toggle(collapse);
                },
                liveHandler(op) {
                    if (op.action === "open") this.openById(op.id);
                    else if (op.action === "close") this.closeById(op.id);
                    else if (op.action === "toggle") this.toggleById(op.id);
                    else if (typeof this[op.action] === "function") this[op.action](op.id, op.value);
                }
            };
            return Collapse;
        ');
    }

    /**
     * 设置面板标题
     * @param string $title 标题文字
     * @return static
     * @ux-example Collapse::make()->title('面板1')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置默认展开状态
     * @param bool $open 是否展开
     * @return static
     * @ux-example Collapse::make()->open()
     * @ux-default true
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example Collapse::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置展开/折叠图标
     * @param string $icon 图标类名（可省略 bi- 前缀）
     * @return static
     * @ux-example Collapse::make()->icon('bi-chevron-down')
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * 设置 LiveAction（点击时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example Collapse::make()->action('togglePanel')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-collapse');
        if ($this->open) {
            $el->class('ux-collapse-open');
        }
        if ($this->disabled) {
            $el->class('ux-collapse-disabled');
        }

        if ($this->action) {
            $el->data('collapse-action', $this->action);
        }

        // 头部
        $headerEl = Element::make('div')->class('ux-collapse-header');

        if ($this->icon) {
            $iconClass = str_starts_with($this->icon, 'bi-') ? $this->icon : 'bi-' . $this->icon;
            $headerEl->child(
                Element::make('i')->class($iconClass)->class('ux-collapse-icon')
            );
        }

        $headerEl->child(
            Element::make('span')->class('ux-collapse-title')->text($this->title)
        );

        // 展开/折叠图标
        $arrowEl = Element::make('span')
            ->class('ux-collapse-arrow')
            ->html('<i class="bi bi-chevron-right"></i>');
        $headerEl->child($arrowEl);

        $el->child($headerEl);

        // 内容区域
        $contentEl = Element::make('div')->class('ux-collapse-content');
        $this->appendChildren($contentEl);
        $el->child($contentEl);

        return $el;
    }
}
