<?php

declare(strict_types=1);

namespace Framework\UX\Menu;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Form;
use Framework\View\Link;
use Framework\View\Text;

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
    protected string $label = 'Dropdown';
    protected array $items = [];
    protected string $position = 'bottom-start';
    protected bool $hover = false;
    protected mixed $customTrigger = null;
    protected bool $noborder = false;

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
