<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Skeleton extends UXComponent
{
    protected string $type = 'text';
    protected int $count = 1;
    protected bool $animated = true;
    protected ?string $width = null;
    protected ?string $height = null;

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function text(): static
    {
        return $this->type('text');
    }

    public function avatar(): static
    {
        return $this->type('avatar');
    }

    public function rect(): static
    {
        return $this->type('rect');
    }

    public function circle(): static
    {
        return $this->type('circle');
    }

    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function animated(bool $animated = true): static
    {
        $this->animated = $animated;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    protected function toElement(): Element
    {
        $containerEl = new Element('div');
        $this->buildElement($containerEl);

        for ($i = 0; $i < $this->count; $i++) {
            $el = new Element('div');
            $el->class('ux-skeleton');
            $el->class("ux-skeleton-{$this->type}");

            if ($this->animated) {
                $el->class('ux-skeleton-animated');
            }

            $style = '';
            if ($this->width) {
                $style .= "width: {$this->width};";
            }
            if ($this->height) {
                $style .= "height: {$this->height};";
            }
            if ($style) {
                $el->style($style);
            }

            $containerEl->child($el);
        }

        return $containerEl;
    }
}
