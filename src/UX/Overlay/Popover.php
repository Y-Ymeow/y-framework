<?php

declare(strict_types=1);

namespace Framework\UX\Overlay;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 气泡卡片
 *
 * 用于点击/悬停显示浮层内容，支持标题、内容、位置、触发方式、箭头、最大宽度。
 *
 * @ux-category Overlay
 * @ux-since 1.0.0
 * @ux-example Popover::make()->title('标题')->content('内容')->trigger('click')->placement('bottom')
 * @ux-example Popover::make()->content($view)->hover()->arrow(false)->maxWidth(300)
 * @ux-js-component popover.js
 * @ux-css popover.css
 */
class Popover extends UXComponent
{
    protected ?string $title = null;
    protected ?string $content = null;
    protected string $placement = 'top';
    protected string $trigger = 'click';
    protected bool $arrow = true;
    protected ?int $maxWidth = null;
    protected bool $open = false;

    /**
     * 设置气泡标题
     * @param string $title 标题文字
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置气泡内容
     * @param string $content 内容文字
     * @return static
     */
    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置气泡位置
     * @param string $placement 位置：top/bottom/left/right
     * @return static
     * @ux-default 'top'
     */
    public function placement(string $placement): static
    {
        $this->placement = $placement;
        return $this;
    }

    /**
     * 顶部气泡
     * @return static
     */
    public function top(): static
    {
        return $this->placement('top');
    }

    /**
     * 底部气泡
     * @return static
     */
    public function bottom(): static
    {
        return $this->placement('bottom');
    }

    /**
     * 左侧气泡
     * @return static
     */
    public function left(): static
    {
        return $this->placement('left');
    }

    /**
     * 右侧气泡
     * @return static
     */
    public function right(): static
    {
        return $this->placement('right');
    }

    /**
     * 设置触发方式
     * @param string $trigger 触发方式：hover/click/focus
     * @return static
     * @ux-default 'click'
     */
    public function trigger(string $trigger): static
    {
        $this->trigger = $trigger;
        return $this;
    }

    /**
     * 悬停触发
     * @return static
     */
    public function hover(): static
    {
        return $this->trigger('hover');
    }

    /**
     * 点击触发
     * @return static
     */
    public function click(): static
    {
        return $this->trigger('click');
    }

    /**
     * 聚焦触发
     * @return static
     */
    public function focus(): static
    {
        return $this->trigger('focus');
    }

    /**
     * 是否显示箭头
     * @param bool $arrow 是否显示
     * @return static
     * @ux-default true
     */
    public function arrow(bool $arrow = true): static
    {
        $this->arrow = $arrow;
        return $this;
    }

    /**
     * 设置最大宽度
     * @param int $width 最大宽度（像素）
     * @return static
     */
    public function maxWidth(int $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    /**
     * 设置打开状态
     * @param bool $open 是否打开
     * @return static
     * @ux-default false
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-popover-wrapper');
        $el->data('popover-placement', $this->placement);
        $el->data('popover-trigger', $this->trigger);

        if (!$this->arrow) {
            $el->data('popover-arrow', 'false');
        }

        if ($this->maxWidth) {
            $el->data('popover-max-width', (string)$this->maxWidth);
        }

        if ($this->open) {
            $el->data('popover-open', 'true');
        }

        // 添加子元素（触发器）
        $this->appendChildren($el);

        // Popover 内容（通过 JS 动态创建）
        $el->data('popover-title', $this->title ?? '');
        $el->data('popover-content', $this->content ?? '');

        return $el;
    }
}
