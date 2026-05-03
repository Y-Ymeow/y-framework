<?php

declare(strict_types=1);

namespace Framework\UX\Menu;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 菜单
 *
 * 用于显示导航菜单，支持多级嵌套、图标、激活状态、水平/垂直布局。
 *
 * @ux-category Menu
 * @ux-since 1.0.0
 * @ux-example Menu::make()->item('首页', '/')->item('关于', '/about')
 * @ux-example Menu::make()->item('产品', '/products')->subItem('产品1', '/p1')->subItem('产品2', '/p2')
 * @ux-js-component menu.js
 * @ux-css menu.css
 */
class Menu extends UXComponent
{
    protected string $direction = 'vertical';
    protected array $items = [];

    /**
     * 设置菜单方向
     * @param string $dir 方向：horizontal/vertical
     * @return static
     * @ux-default 'vertical'
     */
    public function direction(string $dir): static
    {
        $this->direction = $dir;
        return $this;
    }

    /**
     * 水平布局
     * @return static
     */
    public function horizontal(): static
    {
        return $this->direction('horizontal');
    }

    /**
     * 垂直布局
     * @return static
     */
    public function vertical(): static
    {
        return $this->direction('vertical');
    }

    /**
     * 添加菜单项
     * @param string $label 显示文字
     * @param string|null $href 链接地址
     * @param string|null $icon 图标类名（可省略 bi- 前缀）
     * @param bool $active 是否激活状态
     * @return static
     * @ux-example Menu::make()->item('首页', '/', 'home', true)
     */
    public function item(string $label, ?string $href = null, ?string $icon = null, bool $active = false): static
    {
        $this->items[] = [
            'type'   => 'item',
            'label'  => $label,
            'href'   => $href,
            'icon'   => $icon,
            'active' => $active,
        ];
        return $this;
    }

    /**
     * 添加菜单分组（可包含子项）
     * @param string $label 分组标题
     * @param string|null $icon 分组图标
     * @param bool $open 是否默认展开
     * @param string|null $id 分组 ID（自动生成）
     * @return static
     */
    public function group(string $label, ?string $icon = null, bool $open = false, ?string $id = null): static
    {
        $this->items[] = [
            'type'     => 'group',
            'label'    => $label,
            'icon'     => $icon,
            'open'     => $open,
            'id'       => $id ?? $label,
            'children' => [],
        ];
        return $this;
    }

    /**
     * 向最后一个分组添加子项（或作为独立项）
     * @param string $label 显示文字
     * @param string|null $href 链接地址
     * @param string|null $icon 图标类名
     * @param bool $active 是否激活状态
     * @return static
     */
    public function subitem(string $label, ?string $href = null, ?string $icon = null, bool $active = false): static
    {
        $last = count($this->items) - 1;
        if ($last >= 0 && $this->items[$last]['type'] === 'group') {
            $this->items[$last]['children'][] = [
                'type'   => 'item',
                'label'  => $label,
                'href'   => $href,
                'icon'   => $icon,
                'active' => $active,
            ];
        } else {
            $this->items[] = [
                'type'   => 'item',
                'label'  => $label,
                'href'   => $href,
                'icon'   => $icon,
                'active' => $active,
            ];
        }
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
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('ul');
        $this->buildElement($el);

        $el->class('ux-menu', 'list-none', 'm-0', 'p-0');

        if ($this->direction === 'horizontal') {
            $el->class('flex', 'flex-row', 'items-center', 'gap-1');
        } else {
            $el->class('flex', 'flex-col', 'gap-0.5');
        }

        foreach ($this->items as $item) {
            if ($item['type'] === 'divider') {
                $el->child(Element::make('li')->class('ux-menu-divider', 'border-t', 'border-gray-100', 'my-1', 'mx-2', 'list-none'));
                continue;
            }

            if ($item['type'] === 'item') {
                $el->child($this->renderItem($item));
                continue;
            }

            if ($item['type'] === 'group') {
                $el->child($this->renderGroup($item));
            }
        }

        return $el;
    }

    protected function renderItem(array $item): Element
    {
        $li = Element::make('li')->class('ux-menu-item', 'list-none');

        $href = $item['href'] ?? 'javascript:;';
        $link = Element::make('a')
            ->class(
                'ux-menu-link',
                'flex',
                'items-center',
                'gap-2',
                'px-3',
                'py-2',
                'text-sm',
                'text-gray-600',
                'rounded',
                'hover:bg-gray-100',
                'hover:text-gray-900',
                'transition-colors'
            )
            ->attr('href', $href)
            ->data('navigate', '')
            // 核心：利用 data-navigate 系统的自动高亮功能
            ->data('active-target', '.ux-menu-item')
            ->data('active-class', 'ux-menu-item-active');

        if ($item['active']) {
            $li->class('ux-menu-item-active');
        }

        if ($item['icon']) {
            $iconClass = str_starts_with($item['icon'], 'bi-') ? $item['icon'] : 'bi-' . $item['icon'];
            $link->child(
                Element::make('i')->class($iconClass, 'ux-menu-icon', 'text-gray-400', 'text-xs')
            );
        }

        $link->child(
            Element::make('span')->class('ux-menu-label', 'truncate')->text($item['label'])
        );

        $li->child($link);
        return $li;
    }

    protected function renderGroup(array $group): Element
    {
        $li = Element::make('li')->class('ux-menu-group', 'list-none');
        if ($group['open']) {
            $li->class('open');
        }

        // Group header
        $header = Element::make('button')
            ->class(
                'ux-menu-group-header',
                'w-full',
                'flex',
                'items-center',
                'justify-between',
                'px-3',
                'py-2',
                'mx-2',
                'text-xs',
                'font-semibold',
                'text-gray-500',
                'uppercase',
                'tracking-wider',
                'hover:text-gray-700',
                'hover:bg-gray-50',
                'rounded',
                'transition-colors'
            )
            ->attr('type', 'button')
            ->attr('aria-expanded', $group['open'] ? 'true' : 'false');

        if ($this->liveAction) {
            $header->liveAction($this->liveAction, $this->liveEvent ?? 'click');
            $header->data('action-params', json_encode([
                'id'   => $group['id'],
                'open' => !$group['open'],
            ], JSON_UNESCAPED_UNICODE));
        }

        $headerLeft = Element::make('span')->class('flex', 'items-center', 'gap-2');

        if ($group['icon']) {
            $iconClass = str_starts_with($group['icon'], 'bi-') ? $group['icon'] : 'bi-' . $group['icon'];
            $headerLeft->child(
                Element::make('i')->class($iconClass, 'ux-menu-icon', 'text-gray-400')
            );
        }

        $headerLeft->child(
            Element::make('span')->class('ux-menu-label')->text($group['label'])
        );
        $header->child($headerLeft);

        $arrowIcon = $group['open'] ? 'bi-chevron-down' : 'bi-chevron-right';
        $header->child(
            Element::make('i')->class('bi', $arrowIcon, 'ux-menu-arrow', 'text-xs', 'text-gray-400')
        );
        $li->child($header);

        // Submenu
        $submenu = Element::make('ul')
            ->class('ux-menu-submenu', 'list-none', 'm-0', 'p-0', 'space-y-0.5', 'px-2', 'mb-2');
        if (!$group['open']) {
            $submenu->class('hidden');
        }

        foreach ($group['children'] as $child) {
            $submenu->child($this->renderItem($child));
        }

        $li->child($submenu);
        return $li;
    }
}
