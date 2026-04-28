<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Navigate extends UXComponent
{
    protected string $href = '#';
    protected string $text = '';
    protected string $variant = 'primary';
    protected string $size = 'md';
    protected ?string $fragment = null;
    protected string $target = '_self';
    protected bool $replace = false;
    protected ?string $icon = null;
    protected ?string $iconPosition = 'left';
    protected bool $disabled = false;
    protected array $states = [];

    public function href(string $href): static
    {
        $this->href = $href;
        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragment = $name;
        return $this;
    }

    public function target(string $target): static
    {
        $this->target = $target;
        return $this;
    }

    public function blank(): static
    {
        return $this->target('_blank');
    }

    public function replace(bool $replace = true): static
    {
        $this->replace = $replace;
        return $this;
    }

    public function icon(string $icon, string $position = 'left'): static
    {
        $icon = str_starts_with($icon, 'bi-') ? $icon : 'bi-' . $icon;
        $this->icon = $icon;
        $this->iconPosition = $position;
        return $this;
    }

    public function bi(string $name, string $position = 'left'): static
    {
        return $this->icon($name, $position);
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

    public function secondary(): static
    {
        return $this->variant('secondary');
    }

    public function danger(): static
    {
        return $this->variant('danger');
    }

    public function success(): static
    {
        return $this->variant('success');
    }

    public function warning(): static
    {
        return $this->variant('warning');
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

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function state(string $key, mixed $value): static
    {
        $this->states[$key] = $value;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('a');
        $this->buildElement($el);

        $el->class('ux-navigate');
        $el->class("ux-navigate-{$this->variant}");
        $el->class("ux-navigate-{$this->size}");

        if ($this->target !== '_self') {
            $el->attr('target', $this->target);
        }

        if ($this->replace) {
            $el->data('navigate-replace', '');
        }

        if (!empty($this->states)) {
            $el->data('navigate-state', json_encode($this->states, JSON_UNESCAPED_UNICODE));
        }

        if ($this->disabled) {
            $el->class('ux-navigate-disabled');
            $el->attr('aria-disabled', 'true');
            $el->attr('tabindex', '-1');
        } else {
            $el->attr('href', $this->href);
            $el->data('navigate', '');

            if ($this->fragment !== null) {
                $el->data('navigate-fragment', $this->fragment);
            }
        }

        if ($this->icon && $this->iconPosition === 'left') {
            $iconEl = Element::make('i')
                ->class($this->icon)
                ->attr('aria-hidden', 'true');
            $el->child($iconEl);
        }

        if ($this->text) {
            $el->child(Element::make('span')->class('ux-navigate-text')->text($this->text));
        }

        if ($this->icon && $this->iconPosition === 'right') {
            $iconEl = Element::make('i')
                ->class($this->icon)
                ->attr('aria-hidden', 'true');
            $el->child($iconEl);
        }

        return $el;
    }
}
