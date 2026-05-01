<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Breadcrumb extends UXComponent
{
    protected array $items = [];
    protected string $separator = '/';

    public function item(string $label, ?string $link = null): static
    {
        $this->items[] = [
            'label' => $label,
            'link' => $link,
        ];
        return $this;
    }

    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    protected function toElement(): Element
    {
        $navEl = new Element('nav');
        $this->buildElement($navEl);

        $navEl->class('ux-breadcrumb');
        $navEl->attr('aria-label', 'breadcrumb');

        $listEl = Element::make('ol')->class('ux-breadcrumb-list');

        $count = count($this->items);
        foreach ($this->items as $index => $item) {
            $isLast = ($index === $count - 1);

            $itemEl = Element::make('li')->class('ux-breadcrumb-item');
            if ($isLast) {
                $itemEl->class('active');
                $itemEl->attr('aria-current', 'page');
            }

            if ($item['link'] && !$isLast) {
                $itemEl->child(
                    Element::make('a')
                        ->class('ux-breadcrumb-link')
                        ->attr('href', $item['link'])
                        ->text($item['label'])
                );
            } else {
                $itemEl->child(
                    Element::make('span')
                        ->class('ux-breadcrumb-text')
                        ->text($item['label'])
                );
            }

            if (!$isLast) {
                $itemEl->child(
                    Element::make('span')
                        ->class('ux-breadcrumb-separator')
                        ->text($this->separator)
                );
            }

            $listEl->child($itemEl);
        }

        $navEl->child($listEl);

        return $navEl;
    }
}
