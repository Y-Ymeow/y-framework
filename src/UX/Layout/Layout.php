<?php

declare(strict_types=1);

namespace Framework\UX\Layout;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Layout extends UXComponent
{
    protected bool $hasSidebar = false;
    protected bool $sidebarLeft = true;
    protected string $sidebarWidth = '64';
    protected bool $hasHeader = false;
    protected bool $hasFooter = false;
    protected bool $fixedHeader = false;
    protected bool $fixedFooter = false;

    public function sidebar(bool $left = true): static
    {
        $this->hasSidebar = true;
        $this->sidebarLeft = $left;
        return $this;
    }

    public function sidebarLeft(): static
    {
        return $this->sidebar(true);
    }

    public function sidebarRight(): static
    {
        return $this->sidebar(false);
    }

    public function sidebarWidth(int $width): static
    {
        $this->sidebarWidth = (string)$width;
        return $this;
    }

    public function header(bool $fixed = false): static
    {
        $this->hasHeader = true;
        $this->fixedHeader = $fixed;
        return $this;
    }

    public function footer(bool $fixed = false): static
    {
        $this->hasFooter = true;
        $this->fixedFooter = $fixed;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-layout');
        $el->class('min-h-screen');
        $el->class('flex');
        $el->class('flex-col');

        return $el;
    }

    public function renderHeader(mixed $content): Element
    {
        $el = new Element('header');
        $el->class('ux-layout-header');
        $el->class('flex');
        $el->class('items-center');
        $el->class('px-4');
        $el->class('py-3');
        $el->class('bg-white');
        $el->class('border-b');
        $el->class('border-gray-200');

        if ($this->fixedHeader) {
            $el->class('fixed');
            $el->class('top-0');
            $el->class('left-0');
            $el->class('right-0');
            $el->class('z-50');
        }

        $el->child($this->resolveChild($content));

        return $el;
    }

    public function renderSidebar(mixed $content): Element
    {
        $el = new Element('aside');
        $el->class('ux-layout-sidebar');
        $el->class('flex');
        $el->class('flex-col');
        $el->class('bg-gray-50');
        $el->class('border-gray-200');

        if ($this->sidebarLeft) {
            $el->class('border-r');
        } else {
            $el->class('border-l');
        }

        $el->style("width: {$this->sidebarWidth}px; min-width: {$this->sidebarWidth}px;");
        $el->child($this->resolveChild($content));

        return $el;
    }

    public function renderMain(mixed $content): Element
    {
        $el = new Element('main');
        $el->class('ux-layout-main');
        $el->class('flex-1');
        $el->class('overflow-auto');
        $el->child($this->resolveChild($content));

        return $el;
    }

    public function renderFooter(mixed $content): Element
    {
        $el = new Element('footer');
        $el->class('ux-layout-footer');
        $el->class('flex');
        $el->class('items-center');
        $el->class('px-4');
        $el->class('py-3');
        $el->class('bg-white');
        $el->class('border-t');
        $el->class('border-gray-200');

        if ($this->fixedFooter) {
            $el->class('fixed');
            $el->class('bottom-0');
            $el->class('left-0');
            $el->class('right-0');
        }

        $el->child($this->resolveChild($content));

        return $el;
    }

    public function renderBody(mixed $sidebar, mixed $main): Element
    {
        $el = new Element('div');
        $el->class('ux-layout-body');
        $el->class('flex');
        $el->class('flex-1');
        $el->class('overflow-hidden');

        $sidebarEl = $this->renderSidebar($sidebar);
        $mainEl = $this->renderMain($main);

        if ($this->sidebarLeft) {
            $el->child($sidebarEl);
            $el->child($mainEl);
        } else {
            $el->child($mainEl);
            $el->child($sidebarEl);
        }

        return $el;
    }
}
