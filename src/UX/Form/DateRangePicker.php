<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 日期范围选择器
 *
 * 用于选择日期范围（开始日期和结束日期），支持日期时间模式、范围限制、清除、禁用。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example DateRangePicker::make()->value('2026-01-01', '2026-01-31')->placeholder('选择日期范围')
 * @ux-example DateRangePicker::make()->minDate('2026-01-01')->maxDate('2026-12-31')->showTime()
 * @ux-js-component date-range-picker.js
 * @ux-css date-range-picker.css
 */
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

    /**
     * 设置开始日期
     * @param string $value 开始日期字符串 Y-m-d
     * @return static
     * @ux-example DateRangePicker::make()->startValue('2026-01-01')
     */
    public function startValue(string $value): static
    {
        $this->startValue = $value;
        return $this;
    }

    /**
     * 设置结束日期
     * @param string $value 结束日期字符串 Y-m-d
     * @return static
     * @ux-example DateRangePicker::make()->endValue('2026-01-31')
     */
    public function endValue(string $value): static
    {
        $this->endValue = $value;
        return $this;
    }

    /**
     * 设置日期范围
     * @param string $start 开始日期
     * @param string $end 结束日期
     * @return static
     * @ux-example DateRangePicker::make()->value('2026-01-01', '2026-01-31')
     */
    public function value(string $start, string $end): static
    {
        $this->startValue = $start;
        $this->endValue = $end;
        return $this;
    }

    /**
     * 设置占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example DateRangePicker::make()->placeholder('选择日期范围')
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 设置日期格式
     * @param string $format PHP 日期格式
     * @return static
     * @ux-example DateRangePicker::make()->format('Y/m/d')
     * @ux-default 'Y-m-d'
     */
    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example DateRangePicker::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 是否允许清除
     * @param bool $allow 是否允许
     * @return static
     * @ux-example DateRangePicker::make()->allowClear(false)
     * @ux-default true
     */
    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    /**
     * 显示"今天"快捷按钮
     * @param bool $show 是否显示
     * @return static
     * @ux-example DateRangePicker::make()->showToday()
     * @ux-default true
     */
    public function showToday(bool $show = true): static
    {
        $this->showToday = $show;
        return $this;
    }

    /**
     * 设置最小可选日期
     * @param string $date 日期字符串 Y-m-d
     * @return static
     * @ux-example DateRangePicker::make()->minDate('2026-01-01')
     */
    public function minDate(string $date): static
    {
        $this->minDate = $date;
        return $this;
    }

    /**
     * 设置最大可选日期
     * @param string $date 日期字符串 Y-m-d
     * @return static
     * @ux-example DateRangePicker::make()->maxDate('2026-12-31')
     */
    public function maxDate(string $date): static
    {
        $this->maxDate = $date;
        return $this;
    }

    /**
     * 设置 LiveAction（已废弃，请用 liveModel）
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
     * 设置分隔符
     * @param string $separator 分隔符
     * @return static
     * @ux-example DateRangePicker::make()->separator('~')
     * @ux-default '~'
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * 启用时间选择，格式自动切换为 Y-m-d H:i:s
     * @param bool $show 是否启用
     * @return static
     * @ux-example DateRangePicker::make()->showTime()
     * @ux-default true
     */
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
