<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 评分组件
 *
 * 星级评分，支持半星、只读、自定义图标。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Rate::make()->value(3)
 * @ux-example Rate::make()->allowHalf()->value(3.5)
 * @ux-example Rate::make()->readOnly()->value(4)
 * @ux-live-support liveModel
 * @ux-js-component rate
 * @ux-css rate.css
 * @ux-value-type float 0-5
 */
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

    /**
     * 设置星星数量
     * @param int $count 数量
     * @return static
     * @ux-example Rate::make()->count(10)
     * @ux-default 5
     */
    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * 设置默认评分值
     * @param float $value 评分（0-5）
     * @return static
     * @ux-example Rate::make()->value(3.5)
     * @ux-default 0
     */
    public function value(float $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 允许半星选择
     * @param bool $allow 是否允许
     * @return static
     * @ux-example Rate::make()->allowHalf()
     * @ux-default true
     */
    public function allowHalf(bool $allow = true): static
    {
        $this->allowHalf = $allow;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example Rate::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置只读模式（显示但不可交互）
     * @param bool $readOnly 是否只读
     * @return static
     * @ux-example Rate::make()->readOnly()->value(4)
     * @ux-default true
     */
    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    /**
     * 设置自定义图标字符
     * @param string $character 图标字符或 HTML
     * @return static
     * @ux-example Rate::make()->character('<i class="bi bi-heart-fill"></i>')
     */
    public function character(string $character): static
    {
        $this->character = $character;
        return $this;
    }

    /**
     * 设置 LiveAction（评分时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-internal
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置悬停触发 Action
     * @param string $action Action 名称
     * @return static
     */
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
