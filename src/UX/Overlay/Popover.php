<?php

declare(strict_types=1);

namespace Framework\UX\Overlay;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Popover extends UXComponent
{
    protected ?string $title = null;
    protected ?string $content = null;
    protected string $placement = 'top';
    protected string $trigger = 'click';
    protected bool $arrow = true;
    protected ?int $maxWidth = null;
    protected bool $open = false;

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function placement(string $placement): static
    {
        $this->placement = $placement;
        return $this;
    }

    public function top(): static
    {
        return $this->placement('top');
    }

    public function bottom(): static
    {
        return $this->placement('bottom');
    }

    public function left(): static
    {
        return $this->placement('left');
    }

    public function right(): static
    {
        return $this->placement('right');
    }

    public function trigger(string $trigger): static
    {
        $this->trigger = $trigger;
        return $this;
    }

    public function hover(): static
    {
        return $this->trigger('hover');
    }

    public function click(): static
    {
        return $this->trigger('click');
    }

    public function focus(): static
    {
        return $this->trigger('focus');
    }

    public function arrow(bool $arrow = true): static
    {
        $this->arrow = $arrow;
        return $this;
    }

    public function maxWidth(int $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

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
