<?php

declare(strict_types=1);

namespace Framework\UX\Layout;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Grid extends UXComponent
{
    protected int $cols = 1;
    protected string $gap = '4';
    protected string $align = 'stretch';

    public function cols(int $cols): static
    {
        $this->cols = max(1, $cols);
        return $this;
    }

    public function gap(int $gap): static
    {
        $this->gap = (string)$gap;
        return $this;
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

    protected function toElement(): Element
    {
        $element = new Element('div');
        $this->buildElement($element);

        $element->class('ux-grid', 'grid', "grid-cols-{$this->cols}", "gap-{$this->gap}");

        $element->children(...$this->children);

        return $element;
    }
}
