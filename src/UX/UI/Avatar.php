<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Avatar extends UXComponent
{
    protected ?string $src = null;
    protected ?string $name = null;
    protected string $size = 'md';
    protected string $shape = 'circle';
    protected ?string $status = null;

    public function src(string $src): static
    {
        $this->src = $src;
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function shape(string $shape): static
    {
        $this->shape = $shape;
        return $this;
    }

    public function circle(): static
    {
        return $this->shape('circle');
    }

    public function rounded(): static
    {
        return $this->shape('rounded');
    }

    public function status(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    protected function getInitials(): string
    {
        if (!$this->name) return '';
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $w) {
            $initials .= mb_substr($w, 0, 1);
        }
        return mb_strtoupper(mb_substr($initials, 0, 2));
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-avatar');
        $el->class("ux-avatar-{$this->size}");
        $el->class("ux-avatar-{$this->shape}");

        if ($this->src) {
            $el->child(
                Element::make('img')
                    ->class('ux-avatar-img')
                    ->attr('src', $this->src)
                    ->attr('alt', $this->name ?? 'Avatar')
            );
        } elseif ($this->name) {
            $el->child(
                Element::make('span')
                    ->class('ux-avatar-initials')
                    ->text($this->getInitials())
            );
        } else {
            $el->child(
                Element::make('span')
                    ->class('ux-avatar-placeholder')
                    ->text('?')
            );
        }

        if ($this->status) {
            $el->child(
                Element::make('span')
                    ->class('ux-avatar-status')
                    ->class("ux-avatar-status-{$this->status}")
            );
        }

        return $el;
    }
}
