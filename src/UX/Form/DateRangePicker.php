<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DateRangePicker extends UXComponent
{
    protected ?string $startValue = null;
    protected ?string $endValue = null;
    protected ?string $placeholder = null;
    protected ?string $format = 'Y-m-d';
    protected bool $disabled = false;
    protected bool $allowClear = true;
    protected bool $showToday = true;
    protected ?string $minDate = null;
    protected ?string $maxDate = null;
    protected ?string $action = null;
    protected string $separator = '~';
    protected bool $showTime = false;

    public function startValue(string $value): static
    {
        $this->startValue = $value;
        return $this;
    }

    public function endValue(string $value): static
    {
        $this->endValue = $value;
        return $this;
    }

    public function value(string $start, string $end): static
    {
        $this->startValue = $start;
        $this->endValue = $end;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    public function showToday(bool $show = true): static
    {
        $this->showToday = $show;
        return $this;
    }

    public function minDate(string $date): static
    {
        $this->minDate = $date;
        return $this;
    }

    public function maxDate(string $date): static
    {
        $this->maxDate = $date;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    public function showTime(bool $show = true): static
    {
        $this->showTime = $show;
        if ($show && $this->format === 'Y-m-d') {
            $this->format = 'Y-m-d H:i:s';
        }
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-date-range-picker');
        if ($this->disabled) {
            $el->class('ux-date-range-picker-disabled');
        }

        $el->data('date-start', $this->startValue ?? '');
        $el->data('date-end', $this->endValue ?? '');
        $el->data('date-format', $this->format);
        $el->data('date-separator', $this->separator);

        if ($this->showTime) {
            $el->data('show-time', 'true');
        }

        if ($this->minDate) {
            $el->data('date-min', $this->minDate);
        }
        if ($this->maxDate) {
            $el->data('date-max', $this->maxDate);
        }
        if ($this->action) {
            $el->data('date-action', $this->action);
        }

        // 输入框区域
        $inputWrapper = Element::make('div')->class('ux-date-range-picker-input-wrapper');

        $inputEl = Element::make('input')
            ->attr('type', 'text')
            ->attr('readonly', 'true')
            ->class('ux-date-range-picker-input');

        if ($this->startValue && $this->endValue) {
            $inputEl->attr('value', $this->startValue . ' ' . $this->separator . ' ' . $this->endValue);
        }
        if ($this->placeholder) {
            $inputEl->attr('placeholder', $this->placeholder);
        }
        if ($this->disabled) {
            $inputEl->attr('disabled', 'true');
        }

        $inputWrapper->child($inputEl);

        // 日历图标
        $iconEl = Element::make('span')
            ->class('ux-date-range-picker-icon')
            ->html('<i class="bi bi-calendar3-range"></i>');
        $inputWrapper->child($iconEl);

        // 清除按钮
        if ($this->allowClear && ($this->startValue || $this->endValue)) {
            $clearEl = Element::make('span')
                ->class('ux-date-range-picker-clear')
                ->html('<i class="bi bi-x-circle"></i>');
            $inputWrapper->child($clearEl);
        }

        $el->child($inputWrapper);

        // Live 桥接隐藏 input
        $rangeValue = ($this->startValue && $this->endValue) 
            ? $this->startValue . '~' . $this->endValue 
            : '';
        $liveInput = $this->createLiveModelInput($rangeValue);
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
