<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Card extends UXComponent
{
    protected ?string $title = null;
    protected ?string $subtitle = null;
    protected mixed $header = null;
    protected mixed $footer = null;
    protected ?string $image = null;
    protected string $imagePosition = 'top';
    protected string $variant = 'default';

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function header(mixed $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    public function image(string $src, string $position = 'top'): static
    {
        $this->image = $src;
        $this->imagePosition = $position;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function bordered(): static
    {
        return $this->variant('bordered');
    }

    public function shadow(): static
    {
        return $this->variant('shadow');
    }

    public function flat(): static
    {
        return $this->variant('flat');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-card');
        $el->class("ux-card-{$this->variant}");

        if ($this->image && $this->imagePosition === 'top') {
            $el->child(
                Element::make('img')
                    ->class('ux-card-img-top')
                    ->attr('src', $this->image)
                    ->attr('alt', 'Card image')
            );
        }

        if ($this->header || $this->title) {
            $headerEl = Element::make('div')->class('ux-card-header');
            if ($this->header) {
                $headerEl->child($this->resolveChild($this->header));
            } else {
                $headerEl->child(Element::make('h3')->class('ux-card-title')->text($this->title));
                if ($this->subtitle) {
                    $headerEl->child(Element::make('p')->class('ux-card-subtitle')->text($this->subtitle));
                }
            }
            $el->child($headerEl);
        }

        $bodyEl = Element::make('div')->class('ux-card-body');
        $this->appendChildren($bodyEl);
        $el->child($bodyEl);

        if ($this->footer) {
            $el->child(
                Element::make('div')
                    ->class('ux-card-footer')
                    ->child($this->resolveChild($this->footer))
            );
        }

        if ($this->image && $this->imagePosition === 'bottom') {
            $el->child(
                Element::make('img')
                    ->class('ux-card-img-bottom')
                    ->attr('src', $this->image)
                    ->attr('alt', 'Card image')
            );
        }

        return $el;
    }
}
