<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Text;

class Drawer extends UXComponent
{
    protected string $title = '';
    protected string $position = 'right'; // left, right, top, bottom
    protected string $size = 'md'; // sm, md, lg, xl, full
    protected bool $open = false;

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function child(mixed $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function left(): static
    {
        return $this->position('left');
    }
    public function right(): static
    {
        return $this->position('right');
    }
    public function top(): static
    {
        return $this->position('top');
    }
    public function bottom(): static
    {
        return $this->position('bottom');
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
    public function md(): static
    {
        return $this->size('md');
    }
    public function lg(): static
    {
        return $this->size('lg');
    }
    public function xl(): static
    {
        return $this->size('xl');
    }
    public function full(): static
    {
        return $this->size('full');
    }

    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    protected function toElement(): Element
    {
        $this->classes[] = 'ux-drawer';
        $this->classes[] = "ux-drawer-{$this->position}";
        $this->classes[] = "ux-drawer-{$this->size}";
        if ($this->open) $this->classes[] = 'ux-drawer-open';

        $el = Container::make();
        $this->buildElement($el);

        $el->class(...$this->classes)
            ->data('ux-drawer', $this->id)
            ->children(
                Container::make()
                    ->class('ux-drawer-overlay')
                    ->data('ux-drawer-close', $this->id),
                Container::make()
                    ->children(
                        Container::make()
                            ->children(
                                Text::h3()
                                    ->class('ux-drawer-title')
                                    ->text($this->title),

                                Element::make("button")
                                    ->class('ux-drawer-close')
                                    ->data('ux-drawer-close', $this->id)
                                    ->html('&times;')

                            )->class('ux-drawer-header'),

                        Container::make()
                            ->children(...$this->children)
                            ->class('ux-drawer-body')
                    )
                    ->class('ux-drawer-content')
            );
        return $el;
    }

    public function trigger(string $label, string $variant = 'primary'): UXComponent
    {
        return Button::make()
            ->label($label)
            ->variant($variant)
            ->attr('data-ux-drawer-toggle', $this->id);
    }
}
