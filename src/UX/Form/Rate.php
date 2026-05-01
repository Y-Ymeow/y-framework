<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Rate extends UXComponent
{
    protected int $count = 5;
    protected float $value = 0;
    protected bool $allowHalf = false;
    protected bool $disabled = false;
    protected bool $readOnly = false;
    protected ?string $character = null;
    protected ?string $action = null;
    protected ?string $hoverAction = null;

    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function value(float $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function allowHalf(bool $allow = true): static
    {
        $this->allowHalf = $allow;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function character(string $character): static
    {
        $this->character = $character;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function hoverAction(string $action): static
    {
        $this->hoverAction = $action;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-rate');
        $el->data('rate-count', (string)$this->count);
        $el->data('rate-value', (string)$this->value);
        $el->data('rate-allow-half', $this->allowHalf ? 'true' : 'false');

        if ($this->disabled) {
            $el->class('ux-rate-disabled');
            $el->data('rate-disabled', 'true');
        }

        if ($this->readOnly) {
            $el->class('ux-rate-readonly');
            $el->data('rate-readonly', 'true');
        }

        if ($this->action) {
            $el->data('rate-action', $this->action);
        }

        if ($this->hoverAction) {
            $el->data('rate-hover-action', $this->hoverAction);
        }

        // 生成星星
        $character = $this->character ?? '★';
        for ($i = 1; $i <= $this->count; $i++) {
            $starEl = Element::make('span')
                ->class('ux-rate-star')
                ->data('rate-index', (string)$i)
                ->text($character);

            // 设置初始状态
            if ($i <= $this->value) {
                $starEl->class('ux-rate-star-full');
            } elseif ($this->allowHalf && $i - 0.5 <= $this->value) {
                $starEl->class('ux-rate-star-half');
            } else {
                $starEl->class('ux-rate-star-empty');
            }

            $el->child($starEl);

            // 如果需要半星，添加半星层
            if ($this->allowHalf && !$this->disabled && !$this->readOnly) {
                $halfEl = Element::make('span')
                    ->class('ux-rate-star-half-trigger')
                    ->data('rate-index', (string)($i - 0.5));
                $el->child($halfEl);
            }
        }

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput((string)$this->value);
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
