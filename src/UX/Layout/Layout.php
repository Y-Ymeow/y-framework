<?php

declare(strict_types=1);

namespace Framework\UX\Layout;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 页面布局
 *
 * 用于构建页面整体布局，支持侧边栏、页眉、页脚、固定布局、左右侧边栏。
 *
 * @ux-category Layout
 * @ux-since 1.0.0
 * @ux-example Layout::make()->header()->sidebar()->footer()->child($main)
 * @ux-example Layout::make()->header(true)->sidebarLeft()->sidebarWidth(240)->footer(true)
 * @ux-js-component layout.js
 * @ux-css layout.css
 */
class Layout extends UXComponent
{
    protected bool $hasSidebar = false;
    protected bool $sidebarLeft = true;
    protected string $sidebarWidth = '64';
    protected bool $hasHeader = false;
    protected bool $hasFooter = false;
    protected bool $fixedHeader = false;
    protected bool $fixedFooter = false;

    /**
     * 启用侧边栏
     * @param bool $left 是否在左侧（false 为右侧）
     * @return static
     * @ux-example Layout::make()->sidebar()->sidebarLeft()
     * @ux-default left=true
     */
    public function sidebar(bool $left = true): static
    {
        $this->hasSidebar = true;
        $this->sidebarLeft = $left;
        return $this;
    }

    /**
     * 启用左侧边栏
     * @return static
     * @ux-example Layout::make()->sidebarLeft()
     */
    public function sidebarLeft(): static
    {
        return $this->sidebar(true);
    }

    /**
     * 启用右侧边栏
     * @return static
     * @ux-example Layout::make()->sidebarRight()
     */
    public function sidebarRight(): static
    {
        return $this->sidebar(false);
    }

    /**
     * 设置侧边栏宽度
     * @param int $width 宽度（px）
     * @return static
     * @ux-example Layout::make()->sidebarWidth(240)
     * @ux-default 64
     */
    public function sidebarWidth(int $width): static
    {
        $this->sidebarWidth = (string)$width;
        return $this;
    }

    /**
     * 启用页眉
     * @param bool $fixed 是否固定页眉
     * @return static
     * @ux-example Layout::make()->header(true)
     * @ux-default fixed=false
     */
    public function header(bool $fixed = false): static
    {
        $this->hasHeader = true;
        $this->fixedHeader = $fixed;
        return $this;
    }

    /**
     * 启用页脚
     * @param bool $fixed 是否固定页脚
     * @return static
     * @ux-example Layout::make()->footer(true)
     * @ux-default fixed=false
     */
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

    /**
     * 渲染页眉
     * @param mixed $content 页眉内容
     * @return Element
     * @ux-example $layout->renderHeader('页眉文字')
     */
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

    /**
     * 渲染侧边栏
     * @param mixed $content 侧边栏内容
     * @return Element
     * @ux-example $layout->renderSidebar($sidebarContent)
     */
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

    /**
     * 渲染主内容区
     * @param mixed $content 主内容
     * @return Element
     * @ux-example $layout->renderMain($mainContent)
     */
    public function renderMain(mixed $content): Element
    {
        $el = new Element('main');
        $el->class('ux-layout-main');
        $el->class('flex-1');
        $el->class('overflow-auto');
        $el->child($this->resolveChild($content));

        return $el;
    }

    /**
     * 渲染页脚
     * @param mixed $content 页脚内容
     * @return Element
     * @ux-example $layout->renderFooter('© 2024')
     */
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

    /**
     * 渲染主体（侧边栏 + 主内容）
     * @param mixed $sidebar 侧边栏内容
     * @param mixed $main 主内容
     * @return Element
     * @ux-example $layout->renderBody($sidebar, $main)
     */
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
