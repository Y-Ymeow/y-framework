<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Divider extends UXComponent
{
    protected ?string $text = null;
    protected string $orientation = 'center';
    protected string $type = 'horizontal';
    protected bool $dashed = false;
    protected string $variant = 'default';

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function orientation(string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function orientationLeft(): static
    {
        return $this->orientation('left');
    }

    public function orientationRight(): static
    {
        return $this->orientation('right');
    }

    public function orientationCenter(): static
    {
        return $this->orientation('center');
    }

    public function vertical(): static
    {
        $this->type = 'vertical';
        return $this;
    }

    public function horizontal(): static
    {
        $this->type = 'horizontal';
        return $this;
    }

    public function dashed(bool $dashed = true): static
    {
        $this->dashed = $dashed;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function primary(): static
    {
        return $this->variant('primary');
    }

    public function success(): static
    {
        return $this->variant('success');
    }

    public function warning(): static
    {
        return $this->variant('warning');
    }

    public function danger(): static
    {
        return $this->variant('danger');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-divider');
        $el->class("ux-divider-{$this->type}");
        $el->class("ux-divider-{$this->variant}");

        if ($this->dashed) {
            $el->class('ux-divider-dashed');
        }

        if ($this->type === 'horizontal' && $this->text) {
            $el->class("ux-divider-with-text-{$this->orientation}");
            $el->child(
                Element::make('span')
                    ->class('ux-divider-text')
                    ->text($this->text)
            );
        }

        return $el;
    }
}
