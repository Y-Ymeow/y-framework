<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Toast extends UXComponent
{
    protected string $message = '';
    protected string $type = 'info';
    protected int $duration = 3000;
    protected bool $closeable = true;
    protected ?string $title = null;
    protected ?string $icon = null;
    protected string $position = 'top-right';

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

    public function duration(int $ms): static
    {
        $this->duration = $ms;
        return $this;
    }

    public function closeable(bool $closeable = true): static
    {
        $this->closeable = $closeable;
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

    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function topRight(): static
    {
        return $this->position('top-right');
    }

    public function topLeft(): static
    {
        return $this->position('top-left');
    }

    public function bottomRight(): static
    {
        return $this->position('bottom-right');
    }

    public function bottomLeft(): static
    {
        return $this->position('bottom-left');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-toast');
        $el->class("ux-toast-{$this->type}");
        $el->data('toast', 'true');
        $el->data('duration', (string) $this->duration);
        $el->data('position', $this->position);

        if ($this->closeable) {
            $el->data('closeable', 'true');
        }

        $iconMap = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];
        $icon = $this->icon ?? ($iconMap[$this->type] ?? 'ℹ');

        $el->child(Element::make('div')->class('ux-toast-icon')->html($icon));

        $contentEl = Element::make('div')->class('ux-toast-content');
        if ($this->title) {
            $contentEl->child(Element::make('div')->class('ux-toast-title')->text($this->title));
        }
        $contentEl->child(Element::make('div')->class('ux-toast-message')->text($this->message));
        $el->child($contentEl);

        if ($this->closeable) {
            $el->child(
                Element::make('button')
                    ->class('ux-toast-close')
                    ->data('toast-close', '')
                    ->html('&times;')
            );
        }

        return $el;
    }

    public function script(): string
    {
        return "UX.toast.show('{$this->type}', '" . addslashes($this->message) . "', {$this->duration});";
    }

    public static function container(): string
    {
        return '<div class="ux-toast-container" id="ux-toast-container"></div>';
    }
}
