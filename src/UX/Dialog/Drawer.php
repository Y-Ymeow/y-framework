<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Text;

/**
 * 抽屉
 *
 * 用于显示侧边抽屉面板，支持自定义标题、位置、尺寸、打开状态、触发按钮。
 *
 * @ux-category Dialog
 * @ux-since 1.0.0
 * @ux-example Drawer::make()->title('侧边栏')->right()->child('内容')
 * @ux-example Drawer::make()->title('详情')->size('lg')->left()->child($view)
 * @ux-example Drawer::make()->title('顶部')->top()->child('内容')->trigger('打开抽屉')
 * @ux-js-component drawer.js
 * @ux-css drawer.css
 */
class Drawer extends UXComponent
{
    protected static ?string $componentName = 'drawer';

    protected string $title = '';
    protected string $position = 'right'; // left, right, top, bottom
    protected string $size = 'md'; // sm, md, lg, xl, full
    protected bool $open = false;

    protected function init(): void
    {
        $this->registerJs('drawer', '
            const Drawer = {
                open(id) {
                    const el = typeof id === "string" ? document.getElementById(id) : id;
                    if (!el) return;
                    el.classList.add("ux-drawer-open");
                    document.body.style.overflow = "hidden";
                },
                close(id) {
                    const el = id ? (typeof id === "string" ? document.getElementById(id) : id) : document.querySelector(".ux-drawer-open");
                    if (!el) return;
                    el.classList.remove("ux-drawer-open");
                    document.body.style.overflow = "";
                },
                init() {
                    document.addEventListener("click", (e) => {
                        const trigger = e.target.closest("[data-ux-drawer-toggle]");
                        if (trigger) {
                            return Drawer.open(trigger.getAttribute("data-ux-drawer-toggle"));
                        }
                        const close = e.target.closest("[data-ux-drawer-close]");
                        if (close) return Drawer.close();
                        if (e.target.classList.contains("ux-drawer-overlay")) Drawer.close();
                    });
                }
            };
            return Drawer;
        ');
    }

    /**
     * 设置抽屉标题
     * @param string $title 标题文字
     * @return static
     * @ux-example Drawer::make()->title('侧边栏')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 添加抽屉内容
     * @param mixed $child 内容（字符串/Element/组件）
     * @return static
     * @ux-example Drawer::make()->child('抽屉内容')
     */
    public function child(mixed $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * 设置抽屉位置
     * @param string $position 位置：left/right/top/bottom
     * @return static
     * @ux-example Drawer::make()->position('left')
     * @ux-default 'right'
     */
    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    /**
     * 左侧抽屉
     * @return static
     * @ux-example Drawer::make()->left()
     */
    public function left(): static
    {
        return $this->position('left');
    }

    /**
     * 右侧抽屉
     * @return static
     * @ux-example Drawer::make()->right()
     */
    public function right(): static
    {
        return $this->position('right');
    }

    /**
     * 顶部抽屉
     * @return static
     * @ux-example Drawer::make()->top()
     */
    public function top(): static
    {
        return $this->position('top');
    }

    /**
     * 底部抽屉
     * @return static
     * @ux-example Drawer::make()->bottom()
     */
    public function bottom(): static
    {
        return $this->position('bottom');
    }

    /**
     * 设置抽屉尺寸
     * @param string $size 尺寸：sm/md/lg/xl/full
     * @return static
     * @ux-example Drawer::make()->size('lg')
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 小尺寸
     * @return static
     * @ux-example Drawer::make()->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 中等尺寸
     * @return static
     * @ux-example Drawer::make()->md()
     */
    public function md(): static
    {
        return $this->size('md');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example Drawer::make()->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 超大尺寸
     * @return static
     * @ux-example Drawer::make()->xl()
     */
    public function xl(): static
    {
        return $this->size('xl');
    }

    /**
     * 全屏尺寸
     * @return static
     * @ux-example Drawer::make()->full()
     */
    public function full(): static
    {
        return $this->size('full');
    }

    /**
     * 设置打开状态
     * @param bool $open 是否打开
     * @return static
     * @ux-example Drawer::make()->open(true)
     * @ux-default false
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * 生成触发按钮
     * @param string $label 按钮文字
     * @param string $variant 按钮变体
     * @return UXComponent
     * @ux-example $drawer->trigger('打开抽屉')
     */
    public function trigger(string $label, string $variant = 'primary'): UXComponent
    {
        return Button::make()
            ->label($label)
            ->variant($variant)
            ->attr('data-ux-drawer-toggle', $this->id);
    }

    protected function toElement(): Element
    {
        $this->classes[] = 'ux-drawer';
        $this->classes[] = "ux-drawer-{$this->position}";
        $this->classes[] = "ux-drawer-{$this->size}";
        if ($this->open) $this->classes[] = 'ux-drawer-open';

        $el = Container::make();
        $this->buildElement($el);

        $el->class(...$this->classes)
            ->data('ux-drawer', $this->id)
            ->children(
                Container::make()
                    ->class('ux-drawer-overlay')
                    ->data('ux-drawer-close', $this->id),
                Container::make()
                    ->children(
                        Container::make()
                            ->children(
                                Text::h3()
                                    ->class('ux-drawer-title')
                                    ->text($this->title),

                                Element::make("button")
                                    ->class('ux-drawer-close')
                                    ->data('ux-drawer-close', $this->id)
                                    ->html('&times;')

                            )->class('ux-drawer-header'),

                        Container::make()
                            ->children(...$this->children)
                            ->class('ux-drawer-body')
                    )
                    ->class('ux-drawer-content')
            );
        return $el;
    }
}
