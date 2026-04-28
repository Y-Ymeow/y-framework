<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Badge extends UXComponent
{
    protected string $variant = 'default';
    protected string $size = 'md';
    protected bool $pill = false;
    protected bool $dot = false;
    protected string $text = '';

    public function __construct(mixed $text = '')
    {
        parent::__construct();
        $this->text = (string)$text;
    }

    public static function make(mixed $text = ''): static
    {
        return new static($text);
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function default(): static
    {
        return $this->variant('default');
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

    public function pill(bool $pill = true): static
    {
        $this->pill = $pill;
        return $this;
    }

    public function dot(bool $dot = true): static
    {
        $this->dot = $dot;
        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-badge');
        $el->class("ux-badge-{$this->variant}");
        $el->class("ux-badge-{$this->size}");

        if ($this->pill) {
            $el->class('ux-badge-pill');
        }

        if ($this->dot) {
            $el->class('ux-badge-dot');
            $el->child(Element::make('span')->class('ux-badge-dot-indicator'));
        }

        $el->text($this->text);
        $this->appendChildren($el);

        return $el;
    }
}
