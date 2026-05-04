<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

trait HasTailwindTypography
{
    public function fontBold(): static
    {
        return $this->class('font-bold');
    }

    public function fontSemibold(): static
    {
        return $this->class('font-semibold');
    }

    public function fontNormal(): static
    {
        return $this->class('font-normal');
    }

    public function textSm(): static
    {
        return $this->class('text-sm');
    }

    public function textXs(): static
    {
        return $this->class('text-xs');
    }

    public function textLg(): static
    {
        return $this->class('text-lg');
    }

    public function textXl(): static
    {
        return $this->class('text-xl');
    }

    public function text2xl(): static
    {
        return $this->class('text-2xl');
    }

    public function text3xl(): static
    {
        return $this->class('text-3xl');
    }

    public function textGray(string $shade = '500'): static
    {
        return $this->class("text-gray-{$shade}");
    }

    public function textWhite(): static
    {
        return $this->class('text-white');
    }

    public function textCenter(): static
    {
        return $this->class('text-center');
    }

    public function textRight(): static
    {
        return $this->class('text-right');
    }

    public function truncate(): static
    {
        return $this->class('truncate');
    }

    public function uppercase(): static
    {
        return $this->class('uppercase');
    }

    public function leading(string $size = 'normal'): static
    {
        return $this->class("leading-{$size}");
    }

    abstract public function class(string ...$classes): static;
}
