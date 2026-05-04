<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

trait HasTailwindAppearance
{
    public function rounded(string $size = 'lg'): static
    {
        return $this->class("rounded-{$size}");
    }

    public function shadow(string $size = 'md'): static
    {
        return $this->class("shadow-{$size}");
    }

    public function bg(string $color): static
    {
        return $this->class("bg-{$color}");
    }

    public function border(string $color = 'gray-200'): static
    {
        return $this->class("border border-{$color}");
    }

    public function opacity(int $level): static
    {
        return $this->class("opacity-{$level}");
    }

    abstract public function class(string ...$classes): static;
}
