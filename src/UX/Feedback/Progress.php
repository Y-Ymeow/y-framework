<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Progress extends UXComponent
{
    protected int $value = 0;
    protected int $max = 100;
    protected string $variant = 'primary';
    protected bool $showLabel = false;
    protected bool $striped = false;
    protected bool $animated = false;
    protected string $size = 'md';

    public function value(int $value): static
    {
        $this->value = max(0, min($value, $this->max));
        return $this;
    }

    public function max(int $max): static
    {
        $this->max = max(1, $max);
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

    public function info(): static
    {
        return $this->variant('info');
    }

    public function showLabel(bool $show = true): static
    {
        $this->showLabel = $show;
        return $this;
    }

    public function striped(bool $striped = true): static
    {
        $this->striped = $striped;
        return $this;
    }

    public function animated(bool $animated = true): static
    {
        $this->animated = $animated;
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

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-progress');
        $el->class("ux-progress-{$this->size}");
        $el->attr('role', 'progressbar');
        $el->attr('aria-valuenow', (string) $this->value);
        $el->attr('aria-valuemin', '0');
        $el->attr('aria-valuemax', (string) $this->max);

        $percentage = round(($this->value / $this->max) * 100);

        $barEl = Element::make('div')
            ->class('ux-progress-bar')
            ->class("ux-progress-bar-{$this->variant}")
            ->style("width: {$percentage}%");

        if ($this->striped) {
            $barEl->class('ux-progress-bar-striped');
        }
        if ($this->animated) {
            $barEl->class('ux-progress-bar-animated');
        }

        if ($this->showLabel) {
            $barEl->child(
                Element::make('span')
                    ->class('ux-progress-label')
                    ->text("{$percentage}%")
            );
        }

        $el->child($barEl);

        return $el;
    }
}
