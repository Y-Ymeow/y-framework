<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Accordion extends UXComponent
{
    protected array $items = [];
    protected bool $multiple = false;
    protected string $variant = 'default';
    protected bool $dark = false;

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

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function dark(bool $dark = true): static
    {
        $this->dark = $dark;
        return $this;
    }

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
                $headerEl->liveAction($this->liveAction, $this->liveEvent ?? 'click');
                $headerEl->data('action-params', json_encode(['id' => $item['id'], 'open' => !$isOpen]));
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
