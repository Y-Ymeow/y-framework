<?php

declare(strict_types=1);

namespace Framework\UX\Media;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Image extends UXComponent
{
    protected string $src = '';
    protected ?string $alt = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected bool $preview = false;
    protected bool $lazy = false;
    protected ?string $fallback = null;
    protected string $fit = 'fill';

    public function src(string $src): static
    {
        $this->src = $src;
        return $this;
    }

    public function alt(string $alt): static
    {
        $this->alt = $alt;
        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function preview(bool $preview = true): static
    {
        $this->preview = $preview;
        return $this;
    }

    public function lazy(bool $lazy = true): static
    {
        $this->lazy = $lazy;
        return $this;
    }

    public function fallback(string $fallback): static
    {
        $this->fallback = $fallback;
        return $this;
    }

    public function fit(string $fit): static
    {
        $this->fit = $fit;
        return $this;
    }

    public function contain(): static
    {
        return $this->fit('contain');
    }

    public function cover(): static
    {
        return $this->fit('cover');
    }

    public function scaleDown(): static
    {
        return $this->fit('scale-down');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-image');
        $el->class("ux-image-fit-{$this->fit}");
        if ($this->preview) {
            $el->class('ux-image-preview');
        }

        // 图片元素
        $imgEl = Element::make('img')
            ->attr('src', $this->src)
            ->class('ux-image-img');

        if ($this->alt) {
            $imgEl->attr('alt', $this->alt);
        }
        if ($this->width) {
            $imgEl->attr('width', (string)$this->width);
        }
        if ($this->height) {
            $imgEl->attr('height', (string)$this->height);
        }
        if ($this->lazy) {
            $imgEl->attr('loading', 'lazy');
        }
        if ($this->fallback) {
            $imgEl->attr('onerror', "this.src='{$this->fallback}'");
        }

        $el->child($imgEl);

        // 预览遮罩
        if ($this->preview) {
            $maskEl = Element::make('div')
                ->class('ux-image-mask')
                ->html('<i class="bi bi-zoom-in"></i>');
            $el->child($maskEl);
        }

        return $el;
    }
}
