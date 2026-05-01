<?php

declare(strict_types=1);

namespace Framework\UX\Overlay;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Tooltip extends UXComponent
{
    protected string $content = '';
    protected string $placement = 'top';
    protected ?string $trigger = null;
    protected bool $arrow = true;
    protected int $delay = 0;
    protected ?int $maxWidth = null;

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

    public function delay(int $delay): static
    {
        $this->delay = $delay;
        return $this;
    }

    public function maxWidth(int $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-tooltip-wrapper');
        $el->data('tooltip', $this->content);
        $el->data('tooltip-placement', $this->placement);

        if ($this->trigger) {
            $el->data('tooltip-trigger', $this->trigger);
        }

        if (!$this->arrow) {
            $el->data('tooltip-arrow', 'false');
        }

        if ($this->delay > 0) {
            $el->data('tooltip-delay', (string)$this->delay);
        }

        if ($this->maxWidth) {
            $el->data('tooltip-max-width', (string)$this->maxWidth);
        }

        // 添加子元素
        $this->appendChildren($el);

        return $el;
    }
}
