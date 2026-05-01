<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Calendar extends UXComponent
{
    protected ?string $value = null;
    protected string $mode = 'month';
    protected bool $fullscreen = false;
    protected bool $disabled = false;
    protected ?string $action = null;
    protected ?string $headerRender = null;
    protected ?string $cellRender = null;
    protected array $validRange = [];

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function mode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function month(): static
    {
        return $this->mode('month');
    }

    public function year(): static
    {
        return $this->mode('year');
    }

    public function fullscreen(bool $fullscreen = true): static
    {
        $this->fullscreen = $fullscreen;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function validRange(string $start, string $end): static
    {
        $this->validRange = ['start' => $start, 'end' => $end];
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-calendar');
        $el->class("ux-calendar-{$this->mode}");
        if ($this->fullscreen) {
            $el->class('ux-calendar-fullscreen');
        }
        if ($this->disabled) {
            $el->class('ux-calendar-disabled');
        }

        $el->data('calendar-value', $this->value ?? date('Y-m-d'));
        $el->data('calendar-mode', $this->mode);

        if ($this->action) {
            $el->data('calendar-action', $this->action);
        }

        if (!empty($this->validRange)) {
            $el->data('calendar-valid-range', json_encode($this->validRange));
        }

        // 头部
        $headerEl = Element::make('div')->class('ux-calendar-header');

        // 导航按钮
        $navEl = Element::make('div')->class('ux-calendar-nav');
        $navEl->child(Element::make('button')
            ->class('ux-calendar-nav-btn')
            ->attr('data-ux-action', 'prev')
            ->html('<i class="bi bi-chevron-left"></i>'));
        $navEl->child(Element::make('span')
            ->class('ux-calendar-title')
            ->text(date('Y年m月')));
        $navEl->child(Element::make('button')
            ->class('ux-calendar-nav-btn')
            ->attr('data-ux-action', 'next')
            ->html('<i class="bi bi-chevron-right"></i>'));
        $headerEl->child($navEl);

        // 模式切换
        $modeEl = Element::make('div')->class('ux-calendar-mode');
        $modeEl->child(Element::make('button')
            ->class('ux-calendar-mode-btn')
            ->class($this->mode === 'month' ? 'active' : '')
            ->attr('data-mode', 'month')
            ->text('月'));
        $modeEl->child(Element::make('button')
            ->class('ux-calendar-mode-btn')
            ->class($this->mode === 'year' ? 'active' : '')
            ->attr('data-mode', 'year')
            ->text('年'));
        $headerEl->child($modeEl);

        $el->child($headerEl);

        // 日历主体
        $bodyEl = Element::make('div')->class('ux-calendar-body');

        // 星期标题
        $weekdaysEl = Element::make('div')->class('ux-calendar-weekdays');
        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        foreach ($weekdays as $day) {
            $weekdaysEl->child(
                Element::make('div')->class('ux-calendar-weekday')->text($day)
            );
        }
        $bodyEl->child($weekdaysEl);

        // 日期网格
        $daysEl = Element::make('div')->class('ux-calendar-days');
        $bodyEl->child($daysEl);

        $el->child($bodyEl);

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput($this->value ?? date('Y-m-d'));
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
