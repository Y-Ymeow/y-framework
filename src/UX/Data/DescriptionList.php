<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DescriptionList extends UXComponent
{
    protected array $items = [];
    protected int $columns = 3;
    protected string $variant = 'default';
    protected string $size = 'md';
    protected bool $bordered = false;
    protected ?string $title = null;
    protected mixed $extra = null;
    protected string $labelAlign = 'right';
    protected ?string $fragmentName = null;

    public function item(string $label, mixed $value, ?\Closure $render = null): static
    {
        $this->items[] = [
            'label' => $label,
            'value' => $value,
            'render' => $render,
        ];
        return $this;
    }

    public function items(array $items): static
    {
        foreach ($items as $item) {
            $this->item(
                $item['label'] ?? '',
                $item['value'] ?? null,
                $item['render'] ?? null
            );
        }
        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = max(1, $columns);
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function sm(): static
    {
        return $this->size('sm');
    }

    public function lg(): static
    {
        return $this->size('lg');
    }

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function extra(mixed $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function labelAlign(string $align): static
    {
        $this->labelAlign = $align;
        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);
        $wrapper->class('ux-desc-list-wrapper');

        if ($this->title || $this->extra) {
            $headerEl = Element::make('div')->class('ux-desc-list-header');
            if ($this->title) {
                $headerEl->child(Element::make('div')->class('ux-desc-list-title')->text($this->title));
            }
            if ($this->extra) {
                $headerEl->child(Element::make('div')->class('ux-desc-list-extra')->child($this->resolveChild($this->extra)));
            }
            $wrapper->child($headerEl);
        }

        $viewEl = Element::make('div')->class('ux-desc-list');
        $viewEl->class("ux-desc-list-{$this->variant}");
        $viewEl->class("ux-desc-list-{$this->size}");
        $viewEl->class("ux-desc-list-col-{$this->columns}");

        if ($this->bordered) {
            $viewEl->class('ux-desc-list-bordered');
        }

        if ($this->fragmentName) {
            $viewEl->liveFragment($this->fragmentName);
        }

        $rowEl = null;
        $colIndex = 0;

        foreach ($this->items as $index => $item) {
            if ($colIndex % $this->columns === 0) {
                $rowEl = Element::make('div')->class('ux-desc-list-row');
                $viewEl->child($rowEl);
            }

            $itemEl = Element::make('div')->class('ux-desc-list-item');

            $labelEl = Element::make('div')
                ->class('ux-desc-list-item-label')
                ->class("ux-desc-list-label-{$this->labelAlign}")
                ->text($item['label']);
            $itemEl->child($labelEl);

            $valueEl = Element::make('div')->class('ux-desc-list-item-value');

            if (isset($item['render']) && $item['render'] instanceof \Closure) {
                $rendered = ($item['render'])($item['value'], $item);
                if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                    $valueEl->child($this->resolveChild($rendered));
                } elseif (is_string($rendered)) {
                    $valueEl->html($rendered);
                } else {
                    $valueEl->text((string)($item['value'] ?? '-'));
                }
            } else {
                $valueEl->text((string)($item['value'] ?? '-'));
            }

            $itemEl->child($valueEl);
            $rowEl->child($itemEl);

            $colIndex++;
        }

        while ($colIndex % $this->columns !== 0) {
            $emptyItem = Element::make('div')->class('ux-desc-list-item ux-desc-list-item-empty');
            $emptyItem->child(Element::make('div')->class('ux-desc-list-item-label')->text(''));
            $emptyItem->child(Element::make('div')->class('ux-desc-list-item-value')->text(''));
            $rowEl->child($emptyItem);
            $colIndex++;
        }

        $wrapper->child($viewEl);

        return $wrapper;
    }
}
