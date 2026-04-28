<?php

declare(strict_types=1);

namespace Framework\UX\Menu;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Form;
use Framework\View\Link;
use Framework\View\Text;

class Dropdown extends UXComponent
{
    protected string $label = 'Dropdown';
    protected array $items = [];
    protected string $position = 'bottom-start';
    protected bool $hover = false;
    protected mixed $customTrigger = null;
    protected bool $noborder = false;

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

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

    public function noborder(bool $noborder = true): static
    {
        $this->noborder = $noborder;
        return $this;
    }

    /**
     * 传入自定义 Element 作为菜单项（最灵活）
     */
    public function element(mixed $content): static
    {
        $this->items[] = [
            'type'    => 'element',
            'content' => $content,
        ];
        return $this;
    }

    public function divider(): static
    {
        $this->items[] = ['type' => 'divider'];
        return $this;
    }

    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function hover(bool $hover = true): static
    {
        $this->hover = $hover;
        return $this;
    }

    /**
     * 自定义 trigger 元素（替代默认的文字按钮）
     */
    public function customTrigger(mixed $trigger): static
    {
        $this->customTrigger = $trigger;
        return $this;
    }

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
