<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Alert extends UXComponent
{
    protected string $message = '';
    protected string $type = 'info';
    protected bool $dismissible = false;
    protected ?string $title = null;
    protected ?string $icon = null;

    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function success(): static
    {
        return $this->type('success');
    }

    public function error(): static
    {
        return $this->type('error');
    }

    public function warning(): static
    {
        return $this->type('warning');
    }

    public function info(): static
    {
        return $this->type('info');
    }

    public function dismissible(bool $dismissible = true): static
    {
        $this->dismissible = $dismissible;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-alert');
        $el->class("ux-alert-{$this->type}");
        if ($this->dismissible) {
            $el->class('ux-alert-dismissible');
        }
        $el->attr('role', 'alert');

        $iconMap = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];

        $icon = $this->icon ?? ($iconMap[$this->type] ?? null);
        if ($icon) {
            $el->child(Element::make('span')->class('ux-alert-icon')->html($icon));
        }

        $contentEl = Element::make('div')->class('ux-alert-content');

        if ($this->title) {
            $contentEl->child(Element::make('div')->class('ux-alert-title')->text($this->title));
        }

        $contentEl->child(Element::make('div')->class('ux-alert-message')->html($this->message));
        $el->child($contentEl);

        if ($this->dismissible) {
            $el->child(
                Element::make('button')
                    ->attr('type', 'button')
                    ->class('ux-alert-close')
                    ->data('alert-close', '')
                    ->html('&times;')
            );
        }

        return $el;
    }
}
