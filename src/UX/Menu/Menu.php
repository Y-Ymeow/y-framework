<?php

declare(strict_types=1);

namespace Framework\UX\Menu;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Menu extends UXComponent
{
    protected string $direction = 'vertical';
    protected array $items = [];

    public function direction(string $dir): static
    {
        $this->direction = $dir;
        return $this;
    }

    public function horizontal(): static
    {
        return $this->direction('horizontal');
    }

    public function vertical(): static
    {
        return $this->direction('vertical');
    }

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

    public function divider(): static
    {
        $this->items[] = ['type' => 'divider'];
        return $this;
    }

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
                'flex', 'items-center', 'gap-2',
                'px-3', 'py-2',
                'text-sm', 'text-gray-600', 'rounded',
                'hover:bg-gray-100', 'hover:text-gray-900',
                'transition-colors'
            )
            ->attr('href', $href)
            ->data('navigate', '');

        if ($item['active']) {
            $link->class('bg-gray-100', 'text-gray-900', 'font-medium');
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
                'w-full', 'flex', 'items-center', 'justify-between',
                'px-3', 'py-2', 'mx-2',
                'text-xs', 'font-semibold', 'text-gray-500',
                'uppercase', 'tracking-wider',
                'hover:text-gray-700', 'hover:bg-gray-50',
                'rounded', 'transition-colors'
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
