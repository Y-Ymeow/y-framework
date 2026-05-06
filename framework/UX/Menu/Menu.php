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

    protected function init(): void
    {
        $this->registerCss(<<<'CSS'
.ux-menu {
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.ux-menu.horizontal {
    flex-direction: row;
    align-items: center;
    gap: 0.25rem;
}
.ux-menu-item {
    margin-bottom: 2px;
}
.ux-menu-link {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    transition: background-color 0.15s, color 0.15s;
    cursor: pointer;
    white-space: nowrap;
}
.ux-menu-link:hover {
    background-color: #f3f4f6;
    color: #111827;
}
.ux-menu-item-active > .ux-menu-link,
.ux-menu-item-active .ux-menu-link {
    background-color: #eff6ff;
    color: #1d4ed8;
    font-weight: 500;
}
.ux-menu-item-active > .ux-menu-link .ux-menu-icon,
.ux-menu-item-active .ux-menu-link .ux-menu-icon {
    color: #3b82f6;
}
.ux-menu-group {
    margin-top: 0.5rem;
}
.ux-menu-group-header {
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    outline: none;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    transition: background-color 0.15s, color 0.15s;
}
.ux-menu-group-header:hover {
    background-color: #f9fafb;
}
.ux-menu-arrow {
    transition: transform 0.2s ease;
    flex-shrink: 0;
}
.ux-menu-group.open .ux-menu-arrow {
    transform: rotate(90deg);
}
.ux-menu-submenu {
    overflow: hidden;
    transition: max-height 0.25s ease, opacity 0.2s ease;
    padding-top: 2px;
    padding-left: 0.5rem;
    margin: 0;
    margin-bottom: 0.5rem;
}
.ux-menu-group:not(.open) .ux-menu-submenu {
    max-height: 0;
    opacity: 0;
    margin: 0;
    padding: 0;
}
.ux-menu-group.open .ux-menu-submenu {
    max-height: 500px;
    opacity: 1;
}
.ux-menu-divider {
    border-top: 1px solid #f3f4f6;
    margin: 0.5rem 0.75rem;
}
.ux-menu-icon {
    flex-shrink: 0;
    width: 1em;
    text-align: center;
}
.ux-menu-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
CSS
        );

        $this->registerJs('menu', <<<'JS'
return {
    _bound: false,
    init() {
        if (this._bound) return;
        this._bound = true;
        document.addEventListener('click', function(e) {
            var header = e.target.closest('.ux-menu-group-header');
            if (!header) return;
            var group = header.closest('.ux-menu-group');
            if (!group) return;
            e.preventDefault();
            e.stopPropagation();
            var isOpen = group.classList.contains('open');
            if (isOpen) {
                group.classList.remove('open');
                header.setAttribute('aria-expanded', 'false');
            } else {
                group.classList.add('open');
                header.setAttribute('aria-expanded', 'true');
            }
        });
    }
};
JS
        );
    }

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
     * @param string|array $label 显示文字
     * @param string|null $href 链接地址
     * @param string|null $icon 图标类名（可省略 bi- 前缀）
     * @param bool $active 是否激活状态
     * @return static
     * @ux-example Menu::make()->item('首页', '/', 'home', true)
     */
    public function item(string|array $label, ?string $href = null, ?string $icon = null, bool $active = false): static
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
    public function group(string|array $label, ?string $icon = null, bool $open = false, ?string $id = null): static
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
    public function subitem(string|array $label, ?string $href = null, ?string $icon = null, bool $active = false): static
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

        $el->class('ux-menu', 'list-none');

        if ($this->direction === 'horizontal') {
            $el->class('horizontal');
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
                'text-sm',
                'text-gray-600',
                'hover:text-gray-900'
            )
            ->attr('href', $href)
            ->data('navigate', '')
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

        $params = [];
        $defaultText = '';
        $labelKey = $item['label'];
        if (is_array($item['label'])) {
            $labelKey = $item['label'][0];
            $params = is_array($item['label'][1]) ? $item['label'][1] : [];
            $defaultText = $item['label'][2] ?? '';
        }

        $link->child(
            Element::make('span')->class('ux-menu-label', 'truncate')->intl($labelKey, $params, $defaultText)
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

        $header = Element::make('button')
            ->class(
                'ux-menu-group-header',
                'text-xs',
                'font-semibold',
                'text-gray-500',
                'uppercase',
                'tracking-wider',
                'hover:text-gray-700'
            )
            ->attr('type', 'button')
            ->attr('aria-expanded', $group['open'] ? 'true' : 'false');

        $headerLeft = Element::make('span')->class('flex', 'items-center', 'gap-2');

        if ($group['icon']) {
            $iconClass = str_starts_with($group['icon'], 'bi-') ? $group['icon'] : 'bi-' . $group['icon'];
            $headerLeft->child(
                Element::make('i')->class($iconClass, 'ux-menu-icon', 'text-gray-400')
            );
        }

        $groupParams = [];
        $groupDefault = '';
        $groupLabelKey = $group['label'];
        if (is_array($group['label'])) {
            $groupLabelKey = $group['label'][0];
            $groupParams = is_array($group['label'][1]) ? $group['label'][1] : [];
            $groupDefault = $group['label'][2] ?? '';
        }

        $headerLeft->child(
            Element::make('span')->class('ux-menu-label')->intl($groupLabelKey, $groupParams, $groupDefault)
        );
        $header->child($headerLeft);

        $arrowIcon = $group['open'] ? 'bi-chevron-down' : 'bi-chevron-right';
        $header->child(
            Element::make('i')->class('bi', $arrowIcon, 'ux-menu-arrow', 'text-xs', 'text-gray-400')
        );
        $li->child($header);

        $submenu = Element::make('ul')
            ->class('ux-menu-submenu', 'list-none');

        foreach ($group['children'] as $child) {
            $submenu->child($this->renderItem($child));
        }

        $li->child($submenu);
        return $li;
    }
}
