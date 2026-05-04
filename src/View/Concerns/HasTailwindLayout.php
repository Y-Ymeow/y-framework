<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

trait HasTailwindLayout
{
    public function flex(string $direction = 'row'): static
    {
        return $this->class('flex' . ($direction === 'col' ? ' flex-col' : ''));
    }

    public function grid(int $cols = 1): static
    {
        return $this->class("grid grid-cols-{$cols}");
    }

    public function itemsCenter(): static
    {
        return $this->class('items-center');
    }

    public function itemsStart(): static
    {
        return $this->class('items-start');
    }

    public function itemsEnd(): static
    {
        return $this->class('items-end');
    }

    public function justifyBetween(): static
    {
        return $this->class('justify-between');
    }

    public function justifyCenter(): static
    {
        return $this->class('justify-center');
    }

    public function justifyEnd(): static
    {
        return $this->class('justify-end');
    }

    public function wFull(): static
    {
        return $this->class('w-full');
    }

    public function minH(string $size = 'screen'): static
    {
        return $this->class("min-h-{$size}");
    }

    public function overflow(string $type = 'hidden'): static
    {
        return $this->class("overflow-{$type}");
    }

    abstract public function class(string ...$classes): static;
}
