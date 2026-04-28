<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

/**
 * Container — 布局容器
 *
 * 默认 div，可指定其他语义标签：
 *   Container::make()              → <div>
 *   Container::make('section')     → <section>
 *   Container::make('nav')         → <nav>
 *   Container::make('article')     → <article>
 *   Container::make('aside')       → <aside>
 *   Container::make('main')        → <main>
 *   Container::make('header')      → <header>
 *   Container::make('footer')      → <footer>
 */
class Container extends Element
{
    public function __construct(?string $tag = 'div')
    {
        $tag = $tag ?? 'div';
        parent::__construct($tag);
    }

    public static function section(): static { return new static('section'); }
    public static function nav(): static { return new static('nav'); }
    public static function article(): static { return new static('article'); }
    public static function aside(): static { return new static('aside'); }
    public static function main(): static { return new static('main'); }
    public static function header(): static { return new static('header'); }
    public static function footer(): static { return new static('footer'); }

    public function flex(string $direction = 'row'): static
    {
        return $this->class('flex' . ($direction === 'col' ? ' flex-col' : ''));
    }

    public function grid(int $cols = 1): static
    {
        return $this->class("grid grid-cols-{$cols}");
    }

    public function gap(int $size = 4): static
    {
        return $this->class("gap-{$size}");
    }

    public function p(int $size = 4): static
    {
        return $this->class("p-{$size}");
    }

    public function mx(string $size = 'auto'): static
    {
        return $this->class("mx-{$size}");
    }

    public function spaceY(int $size = 4): static
    {
        return $this->class("space-y-{$size}");
    }

    public function spaceX(int $size = 4): static
    {
        return $this->class("space-x-{$size}");
    }

    public function itemsCenter(): static
    {
        return $this->class('items-center');
    }

    public function justifyBetween(): static
    {
        return $this->class('justify-between');
    }

    public function justifyEnd(): static
    {
        return $this->class('justify-end');
    }

    public function wFull(): static
    {
        return $this->class('w-full');
    }

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

    public function overflow(string $type = 'hidden'): static
    {
        return $this->class("overflow-{$type}");
    }

    public function minH(string $size = 'screen'): static
    {
        return $this->class("min-h-{$size}");
    }
}
