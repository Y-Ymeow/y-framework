<?php

declare(strict_types=1);

namespace Framework\UX\Layout;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\View\Container;

class Row extends UXComponent
{
    protected string $justify = 'start';
    protected string $align = 'center';
    protected string $gap = '2';
    protected bool $wrap = true;

    public function justify(string $justify): static
    {
        $this->justify = $justify;
        return $this;
    }

    public function justifyStart(): static
    {
        return $this->justify('start');
    }

    public function justifyCenter(): static
    {
        return $this->justify('center');
    }

    public function justifyEnd(): static
    {
        return $this->justify('end');
    }

    public function justifyBetween(): static
    {
        return $this->justify('between');
    }

    public function align(string $align): static
    {
        $this->align = $align;
        return $this;
    }

    public function alignStart(): static
    {
        return $this->align('start');
    }

    public function alignCenter(): static
    {
        return $this->align('center');
    }

    public function alignEnd(): static
    {
        return $this->align('end');
    }

    public function gap(int $gap): static
    {
        $this->gap = (string)$gap;
        return $this;
    }

    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;
        return $this;
    }

    public function noWrap(): static
    {
        return $this->wrap(false);
    }

    protected function toElement(): Element
    {
        $classes = ['ux-row', 'flex', 'flex-row', "justify-{$this->justify}", "items-{$this->align}", "gap-{$this->gap}"];
        if ($this->wrap) {
            $classes[] = 'flex-wrap';
        }
        $this->classes = array_merge($this->classes, $classes);
        
        $el = Container::make()
            ->class(...$this->classes)
            ->attrs($this->attrs)
            ->children(...$this->children);
        return $el;
    }
}
