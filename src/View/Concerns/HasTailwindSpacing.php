<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

trait HasTailwindSpacing
{
    public function p(int $size = 4): static
    {
        return $this->class("p-{$size}");
    }

    public function px(int $size = 4): static
    {
        return $this->class("px-{$size}");
    }

    public function py(int $size = 4): static
    {
        return $this->class("py-{$size}");
    }

    public function m(int $size = 4): static
    {
        return $this->class("m-{$size}");
    }

    public function mx(string $size = 'auto'): static
    {
        return $this->class("mx-{$size}");
    }

    public function my(int $size = 4): static
    {
        return $this->class("my-{$size}");
    }

    public function gap(int $size = 4): static
    {
        return $this->class("gap-{$size}");
    }

    public function spaceY(int $size = 4): static
    {
        return $this->class("space-y-{$size}");
    }

    public function spaceX(int $size = 4): static
    {
        return $this->class("space-x-{$size}");
    }

    abstract public function class(string ...$classes): static;
}
