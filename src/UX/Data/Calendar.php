<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * Calendar 日历组件
 *
 * 纯展示或可交互的日历组件，支持月份/年份视图切换、日期选中、范围限制。
 *
 * ## JS 交互能力（calendar.js）
 *
 * PHP 定义配置 → JS 自动处理日历渲染和交互：
 *
 * - **初始化**: 扫描 `.ux-calendar` 元素，渲染当前月视图
 * - **导航**: `data-ux-action="prev|next"` 按钮切换月份
 * - **模式切换**: 月份视图 ↔ 年份视图（显示 12 个月选择）
 * - **日期选中**: 点击日期单元格 → 更新值 → 触发 `ux:change` 事件
 * - **范围限制**: 通过 `data-calendar-valid-range` 禁用超出范围的日期
 *
 * ### 数据属性（JS 读取）
 * - `data-calendar-value`: 当前选中的日期（Y-m-d 格式）
 * - `data-calendar-mode`: 视图模式（month | year）
 * - `data-ux-view-year / data-ux-view-month`: 当前显示的年月
 * - `data-calendar-valid-range`: JSON 格式的有效范围 {start, end}
 *
 * @ux-category Data
 * @ux-since 1.0.0
 *
 * @ux-example
 * // 基础日历
 * Calendar::make()
 *
 * // 带默认值和范围限制
 * Calendar::make()
 *     ->value('2026-05-02')
 *     ->validRange(['start' => '2026-01-01', 'end' => '2026-12-31'])
 *     ->liveModel('selectedDate')
 *
 * // 年份模式
 * Calendar::make()->mode('year')
 * @ux-example-end
 */
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

    /**
     * 设置选中日期值
     * @param string $value 日期值（Y-m-d 格式）
     * @return static
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置视图模式
     * @param string $mode 模式：month/year
     * @return static
     * @ux-default 'month'
     */
    public function mode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * 月份视图
     * @return static
     * @ux-example Calendar::make()->month()
     */
    public function month(): static
    {
        return $this->mode('month');
    }

    /**
     * 年份视图
     * @return static
     * @ux-example Calendar::make()->year()
     */
    public function year(): static
    {
        return $this->mode('year');
    }

    /**
     * 设置全屏模式
     * @param bool $fullscreen 是否全屏
     * @return static
     * @ux-default false
     */
    public function fullscreen(bool $fullscreen = true): static
    {
        $this->fullscreen = $fullscreen;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-default false
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置日历选择动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置有效日期范围
     * @param string $start 开始日期（Y-m-d）
     * @param string $end 结束日期（Y-m-d）
     * @return static
     * @ux-example Calendar::make()->validRange('2026-01-01', '2026-12-31')
     */
    public function validRange(string $start, string $end): static
    {
        $this->validRange = ['start' => $start, 'end' => $end];
        return $this;
    }

    /**
     * @ux-internal
     */
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
