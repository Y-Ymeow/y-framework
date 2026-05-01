<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Tag extends UXComponent
{
    protected string $text = '';
    protected string $variant = 'default';
    protected string $size = 'md';
    protected ?string $icon = null;
    protected bool $closable = false;
    protected bool $bordered = false;
    protected ?string $closeAction = null;
    protected ?string $closeEvent = null;

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
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

    public function lg(): static
    {
        return $this->size('lg');
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function closable(bool $closable = true): static
    {
        $this->closable = $closable;
        return $this;
    }

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    public function onClose(string $action, string $event = 'click'): static
    {
        $this->closeAction = $action;
        $this->closeEvent = $event;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-tag');
        $el->class("ux-tag-{$this->variant}");
        $el->class("ux-tag-{$this->size}");

        if ($this->bordered) {
            $el->class('ux-tag-bordered');
        }

        if ($this->closable) {
            $el->class('ux-tag-closable');
        }

        // 图标
        if ($this->icon) {
            $iconClass = str_starts_with($this->icon, 'bi-') ? $this->icon : 'bi-' . $this->icon;
            $el->child(
                Element::make('i')
                    ->class($iconClass)
                    ->class('ux-tag-icon')
            );
        }

        // 文字
        $el->child(
            Element::make('span')
                ->class('ux-tag-text')
                ->text($this->text)
        );

        // 关闭按钮
        if ($this->closable) {
            $closeEl = Element::make('span')
                ->class('ux-tag-close')
                ->html('<i class="bi bi-x"></i>');

            if ($this->closeAction) {
                $closeEl->liveAction($this->closeAction, $this->closeEvent ?? 'click');
            } else {
                $closeEl->data('tag-close', '');
            }

            $el->child($closeEl);
        }

        return $el;
    }
}
