<?php

declare(strict_types=1);

namespace Framework\UX\Menu;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\View\Element\Container;
use Framework\View\Element\Form;
use Framework\View\Element\Link;
use Framework\View\Element\Text;

/**
 * 下拉菜单
 *
 * 用于显示下拉菜单，支持触发元素、菜单项、分组、图标。
 *
 * @ux-category Menu
 * @ux-since 1.0.0
 * @ux-example Dropdown::make()->trigger(Button::make()->label('菜单')->primary())->item('选项1')->item('选项2')
 * @ux-example Dropdown::make()->trigger('更多')->item('编辑')->divider()->item('删除')->danger()
 * @ux-js-component dropdown.js
 * @ux-css dropdown.css
 */
class Dropdown extends UXComponent
{
    protected static ?string $componentName = 'dropdown';

    protected string $label = 'Dropdown';
    protected array $items = [];
    protected string $position = 'bottom-start';
    protected bool $hover = false;
    protected mixed $customTrigger = null;
    protected bool $noborder = false;

    protected function init(): void
    {
        $this->registerJs('dropdown', '
            const Dropdown = {
                toggle(id) {
                    const el = typeof id === "string" ? document.getElementById(id) : id;
                    if (!el) return;
                    el.classList.toggle("ux-open");
                },
                closeAll() {
                    document.querySelectorAll(".ux-dropdown.ux-open").forEach(d => d.classList.remove("ux-open"));
                },
                init() {
                    document.addEventListener("click", (e) => {
                        const trigger = e.target.closest("[data-ux-dropdown-toggle]");
                        if (trigger) {
                            this.toggle(trigger.getAttribute("data-ux-dropdown-toggle") || trigger.closest(".ux-dropdown")?.id);
                            return;
                        }
                        if (!e.target.closest(".ux-dropdown")) this.closeAll();
                    });
                }
            };
            return Dropdown;
        ');

        $this->registerCss(<<<'CSS'
.ux-dropdown {
    position: relative;
    display: inline-flex;
}
.ux-dropdown-menu {
    position: absolute;
    z-index: 50;
    min-width: 12rem;
    padding: 0.25rem 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(4px);
    transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
}
.ux-dropdown.ux-open .ux-dropdown-menu,
.ux-dropdown-hover:hover .ux-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
.ux-dropdown-bottom-start .ux-dropdown-menu {
    top: 100%;
    left: 0;
    margin-top: 0.25rem;
}
.ux-dropdown-bottom-end .ux-dropdown-menu {
    top: 100%;
    right: 0;
    margin-top: 0.25rem;
}
.ux-dropdown-top-start .ux-dropdown-menu {
    bottom: 100%;
    left: 0;
    margin-bottom: 0.25rem;
    transform: translateY(-4px);
}
.ux-dropdown-top-end .ux-dropdown-menu {
    bottom: 100%;
    right: 0;
    margin-bottom: 0.25rem;
    transform: translateY(-4px);
}
.ux-dropdown.ux-open .ux-dropdown-menu,
.ux-dropdown-hover:hover .ux-dropdown-menu {
    transform: translateY(0);
}
.ux-dropdown-item {
    display: block;
}
.ux-dropdown-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.1s;
}
.ux-dropdown-link:hover {
    background-color: #f9fafb;
    color: #111827;
}
.ux-dropdown-divider {
    border-top: 1px solid #f3f4f6;
    margin: 0.25rem 0;
}
.ux-dropdown-arrow::after {
    content: "";
    display: inline-block;
    margin-left: 0.375rem;
    vertical-align: middle;
    border-top: 4px solid currentColor;
    border-right: 4px solid transparent;
    border-left: 4px solid transparent;
}
.ux-dropdown-trigger {
    cursor: pointer;
}
CSS
        );
    }

    /**
     * 设置默认标签文字（当不使用自定义 trigger 时显示）
     * @param string $label 标签文字
     * @return static
     * @ux-default 'Dropdown'
     */
    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * 添加菜单项
     * @param string $label 显示文字
     * @param string|null $url 链接地址
     * @param string|null $icon 图标类名（可省略 bi- 前缀）
     * @param string|null $action LiveAction 名称
     * @param array $params 动作参数
     * @return static
     * @ux-example Dropdown::make()->item('编辑', '/edit', 'pencil', 'editItem')
     */
    public function item(string $label, ?string $url = '#', ?string $icon = null, ?string $action = null, array $params = []): static
    {
        $this->items[] = [
            'label'  => $label,
            'url'    => $url,
            'icon'   => $icon,
            'action' => $action,
            'params' => $params,
            'type'   => 'item',
        ];
        return $this;
    }

    /**
     * 设置无边框样式
     * @param bool $noborder 是否无边框
     * @return static
     * @ux-default false
     */
    public function noborder(bool $noborder = true): static
    {
        $this->noborder = $noborder;
        return $this;
    }

    /**
     * 传入自定义 Element 作为菜单项（最灵活）
     * @param mixed $content 自定义内容（Element 或组件）
     * @return static
     */
    public function element(mixed $content): static
    {
        $this->items[] = [
            'type'    => 'element',
            'content' => $content,
        ];
        return $this;
    }

    /**
     * 添加分隔线
     * @return static
     */
    public function divider(): static
    {
        $this->items[] = ['type' => 'divider'];
        return $this;
    }

    /**
     * 设置菜单弹出位置
     * @param string $position 位置：bottom-start/bottom-end/top-start/top-end 等
     * @return static
     * @ux-default 'bottom-start'
     */
    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    /**
     * 启用悬停触发
     * @param bool $hover 是否悬停触发
     * @return static
     * @ux-default false
     */
    public function hover(bool $hover = true): static
    {
        $this->hover = $hover;
        return $this;
    }

    /**
     * 自定义 trigger 元素（替代默认的文字按钮）
     * @param mixed $trigger 自定义触发元素（Element 或组件）
     * @return static
     * @ux-example Dropdown::make()->customTrigger(Button::make()->label('更多')->icon('ellipsis'))
     */
    public function customTrigger(mixed $trigger): static
    {
        $this->customTrigger = $trigger;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $element = new Element('div');
        $this->buildElement($element);
        $element->class('ux-dropdown', "ux-dropdown-{$this->position}");

        if ($this->hover) {
            $element->class('ux-dropdown-hover');
        }

        $items = [];

        foreach ($this->items as $item) {
            if ($item['type'] === 'divider') {
                $items[] = Container::make()
                    ->class('ux-dropdown-divider');
                continue;
            }

            if ($item['type'] === 'element') {
                $items[] = Container::make()
                    ->class('ux-dropdown-item')
                    ->child($this->resolveChild($item['content']));
                continue;
            }

            $itemAttrs = [];
            if ($item['action']) {
                $itemAttrs['data-action'] = $item['action'];
                if (!empty($item['params'])) {
                    $itemAttrs['data-action-params'] = json_encode($item['params']);
                }
            }

            $link = Link::make($item['url'] ?? 'javascript:;')
                ->class('ux-dropdown-link', 'flex', 'items-center', 'gap-2', 'px-4', 'py-2', 'text-sm', 'text-gray-700', 'hover:bg-gray-50')
                ->attrs($itemAttrs);

            if ($item['icon']) {
                $iconClass = str_starts_with($item['icon'], 'bi-') ? $item['icon'] : 'bi-' . $item['icon'];
                $link->child(Element::make('i')->class($iconClass, 'text-gray-400'));
            }

            $link->child(Text::make()->text($item['label']));

            $items[] = Container::make()
                ->class('ux-dropdown-item')
                ->child($link);
        }

        // Trigger
        if ($this->customTrigger) {
            $triggerEl = $this->resolveChild($this->customTrigger);
            if ($triggerEl instanceof Element) {
                $triggerEl->class('ux-dropdown-trigger');
                $triggerEl->data('ux-dropdown-toggle', $this->id);
            }
            if ($this->noborder) {
                $triggerEl->class('border-0 rounded-none');
            }
            $element->child($triggerEl);
        } else {
            $element->child(
                Form::button($this->label)
                    ->class('ux-dropdown-trigger')
                    ->data('ux-dropdown-toggle', $this->id)
                    ->child(
                        Text::make()
                            ->class('ux-dropdown-arrow')
                    )
            );
        }

        $element->child(
            Container::make()
                ->class('ux-dropdown-menu')
                ->children(...$items)
        );

        return $element;
    }
}
