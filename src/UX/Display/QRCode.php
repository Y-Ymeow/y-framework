<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class QRCode extends UXComponent
{
    protected string $value = '';
    protected int $size = 128;
    protected string $level = 'M';
    protected ?string $icon = null;
    protected int $iconSize = 32;
    protected ?string $color = '#000000';
    protected ?string $bgColor = '#ffffff';

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function size(int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function level(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function icon(string $icon, int $size = 32): static
    {
        $this->icon = $icon;
        $this->iconSize = $size;
        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function bgColor(string $bgColor): static
    {
        $this->bgColor = $bgColor;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-qrcode');
        $el->data('qrcode-value', $this->value);
        $el->data('qrcode-size', (string)$this->size);
        $el->data('qrcode-level', $this->level);

        if ($this->color) {
            $el->data('qrcode-color', $this->color);
        }
        if ($this->bgColor) {
            $el->data('qrcode-bg', $this->bgColor);
        }
        if ($this->icon) {
            $el->data('qrcode-icon', $this->icon);
            $el->data('qrcode-icon-size', (string)$this->iconSize);
        }

        // Canvas 容器
        $canvasEl = Element::make('canvas')
            ->class('ux-qrcode-canvas')
            ->attr('width', (string)$this->size)
            ->attr('height', (string)$this->size);
        $el->child($canvasEl);

        return $el;
    }
}
