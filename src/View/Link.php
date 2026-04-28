<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

/**
 * Link — 链接
 *
 *   Link::make('/users')->text('用户列表')
 *   Link::make('#')->text('锚点')->class('text-blue-600')
 */
class Link extends Element
{
    public function __construct(string $href = '#')
    {
        parent::__construct('a');
        $this->attrs['href'] = $href;
    }

    public function href(string $href): static
    {
        $this->attrs['href'] = $href;
        return $this;
    }

    public function target(string $target): static
    {
        $this->attrs['target'] = $target;
        return $this;
    }

    public function blank(): static
    {
        return $this->target('_blank');
    }

    public function download(?string $filename = null): static
    {
        $this->attrs['download'] = $filename ?? '';
        return $this;
    }
}
