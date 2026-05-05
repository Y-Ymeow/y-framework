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
    protected static ?string $componentName = 'calendar';

    protected ?string $value = null;
    protected string $mode = 'month';
    protected bool $fullscreen = false;
    protected bool $disabled = false;
    protected ?string $action = null;
    protected ?string $headerRender = null;
    protected ?string $cellRender = null;
    protected array $validRange = [];

    protected function init(): void
    {
        $this->registerJs('calendar', '
            const Calendar = {
                init() {
                    document.querySelectorAll(".ux-calendar").forEach(calendar => {
                        this.render(calendar);
                    });

                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const calendar = e.target.closest(".ux-calendar");
                        if (!calendar) return;

                        const prevBtn = e.target.closest("[data-ux-action=\"prev\"]");
                        if (prevBtn) { e.stopPropagation(); this.navigate(calendar, -1); return; }

                        const nextBtn = e.target.closest("[data-ux-action=\"next\"]");
                        if (nextBtn) { e.stopPropagation(); this.navigate(calendar, 1); return; }

                        const modeBtn = e.target.closest(".ux-calendar-mode-btn");
                        if (modeBtn) { e.stopPropagation(); this.switchMode(calendar, modeBtn.dataset.mode); return; }

                        const dayCell = e.target.closest(".ux-calendar-day:not(.disabled)");
                        if (dayCell) { e.stopPropagation(); this.selectDate(calendar, dayCell); return; }

                        const monthCell = e.target.closest(".ux-calendar-month:not(.disabled)");
                        if (monthCell) { e.stopPropagation(); this.selectMonth(calendar, monthCell); return; }
                    });
                },
                parseDate(str) {
                    if (!str || typeof str !== "string") return null;
                    const parts = str.split("-");
                    if (parts.length !== 3) return null;
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    const d = parseInt(parts[2], 10);
                    if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
                    return { year: y, month: m, day: d };
                },
                getViewMonth(calendar) {
                    const vy = parseInt(calendar.dataset.uxViewYear, 10);
                    const vm = parseInt(calendar.dataset.uxViewMonth, 10);
                    if (!isNaN(vy) && !isNaN(vm) && vy > 0 && vm >= 1 && vm <= 12) return { year: vy, month: vm };
                    const val = calendar.dataset.calendarValue;
                    const parsed = this.parseDate(val);
                    if (parsed) return { year: parsed.year, month: parsed.month };
                    const now = new Date();
                    return { year: now.getFullYear(), month: now.getMonth() + 1 };
                },
                render(calendar) {
                    const mode = calendar.dataset.calendarMode || "month";
                    const view = this.getViewMonth(calendar);
                    calendar.dataset.uxViewYear = String(view.year);
                    calendar.dataset.uxViewMonth = String(view.month);
                    const title = calendar.querySelector(".ux-calendar-title");
                    if (title) {
                        if (mode === "month") title.textContent = `${view.year}年${view.month}月`;
                        else title.textContent = `${view.year}年`;
                    }
                    if (mode === "month") this.renderMonthView(calendar, view.year, view.month);
                    else this.renderYearView(calendar, view.year);
                },
                renderMonthView(calendar, year, month) {
                    if (isNaN(year) || isNaN(month) || year < 1 || month < 1 || month > 12) {
                        const now = new Date();
                        year = now.getFullYear();
                        month = now.getMonth() + 1;
                    }
                    const daysContainer = calendar.querySelector(".ux-calendar-days");
                    if (!daysContainer) return;
                    daysContainer.className = "ux-calendar-days";
                    const weekdaysEl = calendar.querySelector(".ux-calendar-weekdays");
                    if (weekdaysEl) weekdaysEl.style.display = "";
                    const today = this.formatDate(new Date());
                    const selectedDate = calendar.dataset.calendarValue;
                    const validRange = this.getValidRange(calendar);
                    const firstDay = new Date(year, month - 1, 1);
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const startDayOfWeek = firstDay.getDay();
                    const prevMonthLastDay = new Date(year, month, 0).getDate();
                    let html = "";
                    for (let i = startDayOfWeek - 1; i >= 0; i--) {
                        const day = prevMonthLastDay - i;
                        html += `<div class="ux-calendar-day other-month">${day}</div>`;
                    }
                    for (let day = 1; day <= daysInMonth; day++) {
                        const currentDateStr = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                        const isToday = currentDateStr === today;
                        const isSelected = selectedDate === currentDateStr;
                        const isDisabled = this.isOutOfRange(currentDateStr, validRange);
                        let classes = "ux-calendar-day";
                        if (isToday) classes += " today";
                        if (isSelected) classes += " selected";
                        if (isDisabled) classes += " disabled";
                        html += `<div class="${classes}" data-date="${currentDateStr}">${day}</div>`;
                    }
                    const totalCells = 42;
                    const remainingCells = totalCells - startDayOfWeek - daysInMonth;
                    for (let day = 1; day <= remainingCells; day++) {
                        html += `<div class="ux-calendar-day other-month">${day}</div>`;
                    }
                    daysContainer.innerHTML = html;
                },
                renderYearView(calendar, year) {
                    if (isNaN(year) || year < 1) year = new Date().getFullYear();
                    const bodyEl = calendar.querySelector(".ux-calendar-body");
                    if (!bodyEl) return;
                    const selectedDate = calendar.dataset.calendarValue;
                    const parsed = this.parseDate(selectedDate);
                    const selectedYear = parsed ? parsed.year : null;
                    const selectedMonth = parsed ? parsed.month : null;
                    const weekdaysEl = bodyEl.querySelector(".ux-calendar-weekdays");
                    if (weekdaysEl) weekdaysEl.style.display = "none";
                    let daysContainer = bodyEl.querySelector(".ux-calendar-days");
                    if (!daysContainer) {
                        daysContainer = document.createElement("div");
                        daysContainer.className = "ux-calendar-days ux-calendar-months";
                        bodyEl.appendChild(daysContainer);
                    } else {
                        daysContainer.className = "ux-calendar-days ux-calendar-months";
                    }
                    const months = ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"];
                    let html = "";
                    months.forEach((monthName, index) => {
                        const isSelected = year === selectedYear && (index + 1) === selectedMonth;
                        let classes = "ux-calendar-month";
                        if (isSelected) classes += " selected";
                        html += `<div class="${classes}" data-month="${index + 1}">${monthName}</div>`;
                    });
                    daysContainer.innerHTML = html;
                },
                getValidRange(calendar) {
                    const raw = calendar.dataset.calendarValidRange;
                    if (!raw) return null;
                    try { return JSON.parse(raw); } catch { return null; }
                },
                isOutOfRange(dateStr, validRange) {
                    if (!validRange) return false;
                    if (validRange.start && dateStr < validRange.start) return true;
                    if (validRange.end && dateStr > validRange.end) return true;
                    return false;
                },
                navigate(calendar, direction) {
                    const mode = calendar.dataset.calendarMode || "month";
                    const view = this.getViewMonth(calendar);
                    let year = view.year;
                    let month = view.month;
                    if (mode === "month") {
                        month += direction;
                        if (month < 1) { month = 12; year--; }
                        if (month > 12) { month = 1; year++; }
                    } else {
                        year += direction;
                    }
                    calendar.dataset.uxViewYear = String(year);
                    calendar.dataset.uxViewMonth = String(month);
                    this.render(calendar);
                },
                switchMode(calendar, mode) {
                    calendar.dataset.calendarMode = mode;
                    calendar.querySelectorAll(".ux-calendar-mode-btn").forEach(btn => {
                        btn.classList.toggle("active", btn.dataset.mode === mode);
                    });
                    const weekdaysEl = calendar.querySelector(".ux-calendar-weekdays");
                    if (weekdaysEl) weekdaysEl.style.display = mode === "month" ? "" : "none";
                    this.render(calendar);
                },
                selectDate(calendar, dayCell) {
                    const date = dayCell.dataset.date;
                    if (!date) return;
                    const validRange = this.getValidRange(calendar);
                    if (this.isOutOfRange(date, validRange)) return;
                    calendar.dataset.calendarValue = date;
                    calendar.querySelectorAll(".ux-calendar-day").forEach(cell => cell.classList.remove("selected"));
                    dayCell.classList.add("selected");
                    calendar.dispatchEvent(new CustomEvent("ux:change", { detail: { value: date }, bubbles: true }));
                },
                selectMonth(calendar, monthCell) {
                    const month = parseInt(monthCell.dataset.month, 10);
                    if (isNaN(month)) return;
                    const view = this.getViewMonth(calendar);
                    calendar.dataset.uxViewYear = String(view.year);
                    calendar.dataset.uxViewMonth = String(month);
                    this.switchMode(calendar, "month");
                },
                formatDate(date) {
                    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
                },
                getValue(calendar) {
                    return calendar.dataset.calendarValue;
                },
                setValue(calendar, date) {
                    if (!date) return;
                    calendar.dataset.calendarValue = date;
                    const parsed = this.parseDate(date);
                    if (parsed) {
                        calendar.dataset.uxViewYear = String(parsed.year);
                        calendar.dataset.uxViewMonth = String(parsed.month);
                        this.render(calendar);
                    }
                }
            };
            return Calendar;
        ');

        $this->registerCss(<<<'CSS'
.ux-calendar {
    width: 20rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
    font-size: 0.875rem;
}
.ux-calendar-disabled {
    opacity: 0.5;
    pointer-events: none;
}
.ux-calendar-fullscreen {
    width: 100%;
}
.ux-calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}
.ux-calendar-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.ux-calendar-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    background: none;
    border-radius: 0.25rem;
    color: #6b7280;
    cursor: pointer;
    transition: background-color 0.15s, color 0.15s;
}
.ux-calendar-nav-btn:hover {
    background: #f3f4f6;
    color: #374151;
}
.ux-calendar-title {
    font-weight: 600;
    color: #111827;
    min-width: 6rem;
    text-align: center;
}
.ux-calendar-mode {
    display: flex;
    gap: 0.125rem;
    background: #f3f4f6;
    border-radius: 0.25rem;
    padding: 0.125rem;
}
.ux-calendar-mode-btn {
    padding: 0.25rem 0.5rem;
    border: none;
    background: transparent;
    border-radius: 0.125rem;
    font-size: 0.75rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
}
.ux-calendar-mode-btn.active {
    background: #fff;
    color: #374151;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.ux-calendar-body {
    padding: 0.5rem;
}
.ux-calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0;
    margin-bottom: 0.25rem;
}
.ux-calendar-weekday {
    text-align: center;
    font-size: 0.75rem;
    font-weight: 500;
    color: #9ca3af;
    padding: 0.375rem 0;
}
.ux-calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0;
}
.ux-calendar-day {
    text-align: center;
    padding: 0.375rem 0;
    border-radius: 0.25rem;
    cursor: pointer;
    color: #374151;
    transition: background-color 0.1s;
}
.ux-calendar-day:hover {
    background: #f3f4f6;
}
.ux-calendar-day.other-month {
    color: #d1d5db;
}
.ux-calendar-day.today {
    font-weight: 600;
    color: #3b82f6;
}
.ux-calendar-day.selected {
    background: #3b82f6;
    color: #fff;
}
.ux-calendar-day.selected:hover {
    background: #2563eb;
}
.ux-calendar-day.disabled {
    color: #e5e7eb;
    cursor: not-allowed;
}
.ux-calendar-day.disabled:hover {
    background: transparent;
}
.ux-calendar-months {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}
.ux-calendar-month {
    text-align: center;
    padding: 0.625rem;
    border-radius: 0.25rem;
    cursor: pointer;
    color: #374151;
    transition: background-color 0.1s;
}
.ux-calendar-month:hover {
    background: #f3f4f6;
}
.ux-calendar-month.selected {
    background: #3b82f6;
    color: #fff;
}
.ux-calendar-month.disabled {
    color: #e5e7eb;
    cursor: not-allowed;
}
CSS
        );
    }

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
