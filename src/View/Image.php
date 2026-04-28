<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

/**
 * Image — 图片
 *
 *   Image::make('/photo.jpg')->alt('照片')->class('w-full')
 */
class Image extends Element
{
    public function __construct(string $src = '')
    {
        parent::__construct('img');
        if ($src) $this->attrs['src'] = $src;
    }
    
    public function src(string $src): static
    {
        $this->attrs['src'] = $src;
        return $this;
    }

    public function alt(string $alt): static
    {
        $this->attrs['alt'] = $alt;
        return $this;
    }

    public function width(int|string $w): static
    {
        $this->attrs['width'] = (string)$w;
        return $this;
    }

    public function height(int|string $h): static
    {
        $this->attrs['height'] = (string)$h;
        return $this;
    }

    public function objectCover(): static
    {
        return $this->class('object-cover');
    }

    public function objectContain(): static
    {
        return $this->class('object-contain');
    }

    public function rounded(string $size = 'lg'): static
    {
        return $this->class("rounded-{$size}");
    }
}
