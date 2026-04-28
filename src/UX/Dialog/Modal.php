<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;

class Modal extends UXComponent
{
    protected string $title = '';
    protected string $content = '';
    protected string $size = 'md';
    protected bool $closeable = true;
    protected bool $backdrop = true;
    protected bool $centered = true;
    protected mixed $footer = null;
    protected bool $open = false;

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function content(mixed $content): static
    {
        $this->content = is_string($content) ? $content : $this->resolveValue($content);
        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function sm(): static { return $this->size('sm'); }
    public function lg(): static { return $this->size('lg'); }
    public function xl(): static { return $this->size('xl'); }
    public function fullscreen(): static { return $this->size('fullscreen'); }

    public function closeable(bool $closeable = true): static
    {
        $this->closeable = $closeable;
        return $this;
    }

    public function backdrop(bool $backdrop = true): static
    {
        $this->backdrop = $backdrop;
        return $this;
    }

    public function centered(bool $centered = true): static
    {
        $this->centered = $centered;
        return $this;
    }

    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-modal');
        if ($this->open) {
            $el->class('ux-modal-open');
        }

        if ($this->backdrop) {
            $el->child(
                Element::make('div')
                    ->class('ux-modal-backdrop')
                    ->data('ux-modal-close', $this->id)
            );
        }

        $dialogEl = Element::make('div');
        $dialogEl->class('ux-modal-dialog');
        $dialogEl->class("ux-modal-{$this->size}");
        if ($this->centered) {
            $dialogEl->class('ux-modal-centered');
        }

        $contentEl = Element::make('div')->class('ux-modal-content');

        if ($this->title || $this->closeable) {
            $headerEl = Element::make('div')->class('ux-modal-header');
            if ($this->title) {
                $headerEl->child(Element::make('h3')->class('ux-modal-title')->text($this->title));
            }
            if ($this->closeable) {
                $headerEl->child(
                    Element::make('button')
                        ->attr('type', 'button')
                        ->class('ux-modal-close')
                        ->data('ux-modal-close', $this->id)
                        ->html('&times;')
                );
            }
            $contentEl->child($headerEl);
        }

        $contentEl->child(
            Element::make('div')
                ->class('ux-modal-body')
                ->html($this->content)
        );

        if ($this->footer) {
            $contentEl->child(
                Element::make('div')
                    ->class('ux-modal-footer')
                    ->child($this->resolveChild($this->footer))
            );
        }

        $dialogEl->child($contentEl);
        $el->child($dialogEl);

        return $el;
    }

    public function trigger(string $label, string $variant = 'primary'): string
    {
        return Button::make()
            ->label($label)
            ->variant($variant)
            ->attr('data-ux-modal-open', $this->id)
            ->render();
    }
}
