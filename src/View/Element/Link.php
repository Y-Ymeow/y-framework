<?php

declare(strict_types=1);

namespace Framework\View\Element;

use Framework\View\Base\Element;

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
