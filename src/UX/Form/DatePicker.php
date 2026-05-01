<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DatePicker extends UXComponent
{
    protected ?string $value = null;
    protected ?string $placeholder = null;
    protected ?string $format = 'Y-m-d';
    protected bool $disabled = false;
    protected bool $allowClear = true;
    protected bool $showToday = true;
    protected ?string $minDate = null;
    protected ?string $maxDate = null;
    protected ?string $action = null;
    protected bool $showTime = false;
    protected int $timeHour = 0;
    protected int $timeMinute = 0;
    protected int $timeSecond = 0;

    public function value(string $value): static
    {
        $this->value = $value;
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

    public function showTime(bool $show = true): static
    {
        $this->showTime = $show;
        if ($show && $this->format === 'Y-m-d') {
            $this->format = 'Y-m-d H:i:s';
        }
        return $this;
    }

    public function timeHour(int $hour): static
    {
        $this->timeHour = max(0, min(23, $hour));
        return $this;
    }

    public function timeMinute(int $minute): static
    {
        $this->timeMinute = max(0, min(59, $minute));
        return $this;
    }

    public function timeSecond(int $second): static
    {
        $this->timeSecond = max(0, min(59, $second));
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-date-picker');
        if ($this->disabled) {
            $el->class('ux-date-picker-disabled');
        }

        $el->data('date-value', $this->value ?? '');
        $el->data('date-format', $this->format);

        if ($this->showTime) {
            $el->data('show-time', 'true');
            $el->data('time-hour', (string)$this->timeHour);
            $el->data('time-minute', (string)$this->timeMinute);
            $el->data('time-second', (string)$this->timeSecond);
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
        $inputWrapper = Element::make('div')->class('ux-date-picker-input-wrapper');

        $inputEl = Element::make('input')
            ->attr('type', 'text')
            ->attr('readonly', 'true')
            ->class('ux-date-picker-input');

        if ($this->value) {
            $inputEl->attr('value', $this->value);
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
            ->class('ux-date-picker-icon')
            ->html('<i class="bi bi-calendar3"></i>');
        $inputWrapper->child($iconEl);

        // 清除按钮
        if ($this->allowClear && $this->value) {
            $clearEl = Element::make('span')
                ->class('ux-date-picker-clear')
                ->html('<i class="bi bi-x-circle"></i>');
            $inputWrapper->child($clearEl);
        }

        $el->child($inputWrapper);

        // 生成日历下拉面板
        $el->child($this->generateCalendarDropdown());

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput($this->value ?? '');
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }

    protected function generateCalendarDropdown(): Element
    {
        $dropdown = Element::make('div')->class('ux-date-picker-dropdown');
        
        // 日历头部
        $header = Element::make('div')->class('ux-date-picker-header');
        $header->child(Element::make('button')->class('ux-date-picker-nav-btn')->attr('data-ux-action', 'prev-year')->html('<i class="bi bi-chevron-double-left"></i>'));
        $header->child(Element::make('button')->class('ux-date-picker-nav-btn')->attr('data-ux-action', 'prev-month')->html('<i class="bi bi-chevron-left"></i>'));
        $header->child(Element::make('span')->class('ux-date-picker-title')->text(date('Y年m月')));
        $header->child(Element::make('button')->class('ux-date-picker-nav-btn')->attr('data-ux-action', 'next-month')->html('<i class="bi bi-chevron-right"></i>'));
        $header->child(Element::make('button')->class('ux-date-picker-nav-btn')->attr('data-ux-action', 'next-year')->html('<i class="bi bi-chevron-double-right"></i>'));
        $dropdown->child($header);

        // 星期标题
        $weekdays = Element::make('div')->class('ux-date-picker-weekdays');
        $weekdayNames = ['日', '一', '二', '三', '四', '五', '六'];
        foreach ($weekdayNames as $name) {
            $weekdays->child(Element::make('div')->class('ux-date-picker-weekday')->text($name));
        }
        $dropdown->child($weekdays);

        // 日期网格
        $daysGrid = Element::make('div')->class('ux-date-picker-days');
        $daysGrid->data('calendar-days', '');
        
        // 生成当前月的日期
        $year = date('Y');
        $month = date('m');
        $firstDay = strtotime("$year-$month-01");
        $daysInMonth = date('t', $firstDay);
        $startWeekday = date('w', $firstDay);
        
        // 上个月的日期
        $prevMonthDays = date('t', strtotime('-1 month', $firstDay));
        for ($i = $startWeekday - 1; $i >= 0; $i--) {
            $day = $prevMonthDays - $i;
            $daysGrid->child(Element::make('button')
                ->class('ux-date-picker-day other-month')
                ->attr('data-date', date('Y-m-d', strtotime("-1 month +$day days", $firstDay)))
                ->text((string)$day));
        }
        
        // 当前月的日期
        $today = date('Y-m-d');
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%s-%s-%02d', $year, $month, $day);
            $dayEl = Element::make('button')
                ->class('ux-date-picker-day')
                ->attr('data-date', $date)
                ->text((string)$day);
            
            if ($date === $today) {
                $dayEl->class('today');
            }
            if ($date === $this->value) {
                $dayEl->class('selected');
            }
            
            $daysGrid->child($dayEl);
        }
        
        // 下个月的日期
        $remainingDays = 42 - ($startWeekday + $daysInMonth);
        for ($day = 1; $day <= $remainingDays; $day++) {
            $daysGrid->child(Element::make('button')
                ->class('ux-date-picker-day other-month')
                ->attr('data-date', date('Y-m-d', strtotime("+$day days", strtotime("$year-$month-$daysInMonth"))))
                ->text((string)$day));
        }
        
        $dropdown->child($daysGrid);

        // 底部
        if ($this->showToday || $this->showTime) {
            $footer = Element::make('div')->class('ux-date-picker-footer');

            if ($this->showTime) {
                $timePanel = Element::make('div')->class('ux-date-picker-time');
                $timePanel->child(Element::make('select')
                    ->class('ux-date-picker-time-hour')
                    ->attr('data-ux-action', 'set-hour'));
                $timePanel->child(Element::make('span')
                    ->class('ux-date-picker-time-sep')
                    ->text(':'));
                $timePanel->child(Element::make('select')
                    ->class('ux-date-picker-time-minute')
                    ->attr('data-ux-action', 'set-minute'));
                $timePanel->child(Element::make('span')
                    ->class('ux-date-picker-time-sep')
                    ->text(':'));
                $timePanel->child(Element::make('select')
                    ->class('ux-date-picker-time-second')
                    ->attr('data-ux-action', 'set-second'));
                $footer->child($timePanel);
            }

            if ($this->showToday) {
                $footer->child(Element::make('button')
                    ->class('ux-date-picker-today-btn')
                    ->attr('data-ux-action', 'today')
                    ->text('今天'));
            }

            if ($this->showTime) {
                $footer->child(Element::make('button')
                    ->class('ux-date-picker-confirm-btn')
                    ->attr('data-ux-action', 'confirm')
                    ->text('确定'));
            }

            $dropdown->child($footer);
        }

        return $dropdown;
    }
}
