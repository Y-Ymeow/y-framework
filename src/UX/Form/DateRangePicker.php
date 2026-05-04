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
    protected static ?string $componentName = 'dateRangePicker';

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

    protected function init(): void
    {
        $this->registerJs('dateRangePicker', '
            const DateRangePicker = {
                selecting: null,
                init() {
                    document.querySelectorAll(".ux-date-range-picker").forEach(picker => {
                        const startValue = picker.dataset.dateStart;
                        const endValue = picker.dataset.dateEnd;
                        if (startValue || endValue) {
                            const separator = picker.dataset.dateSeparator || "~";
                            const display = startValue && endValue ? `${startValue} ${separator} ${endValue}` : (startValue || endValue || "");
                            const input = picker.querySelector(".ux-date-range-picker-input");
                            if (input && !input.value) input.value = display;
                        }
                    });

                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const picker = e.target.closest(".ux-date-range-picker");
                        if (!picker) { this.hideAll(); return; }
                        const clear = e.target.closest(".ux-date-range-picker-clear");
                        if (clear) { e.stopPropagation(); this.clear(picker); return; }
                        const day = e.target.closest(".ux-date-range-picker-day");
                        if (day) { e.stopPropagation(); const date = day.dataset.date; if (date) this.selectDate(picker, date); return; }
                        const navBtn = e.target.closest(".ux-date-range-picker-nav-btn");
                        if (navBtn) { e.stopPropagation(); const action = navBtn.dataset.uxAction; const calIdx = parseInt(navBtn.dataset.calendar || "0", 10); if (action) this.handleNav(picker, action, calIdx); return; }
                        const todayBtn = e.target.closest(".ux-date-range-picker-today-btn");
                        if (todayBtn) { e.stopPropagation(); this.selectToday(picker); return; }
                        const confirmBtn = e.target.closest(".ux-date-range-picker-confirm-btn");
                        if (confirmBtn) { e.stopPropagation(); this.confirm(picker); return; }
                        const inputWrapper = e.target.closest(".ux-date-range-picker-input-wrapper");
                        if (inputWrapper) { this.toggle(picker); return; }
                    });
                    document.addEventListener("change", (e) => {
                        if (!e.target || !e.target.classList) return;
                        if (e.target.classList.contains("ux-date-range-picker-time-hour") || e.target.classList.contains("ux-date-range-picker-time-minute") || e.target.classList.contains("ux-date-range-picker-time-second")) {
                            const picker = e.target.closest(".ux-date-range-picker");
                            if (picker) this.updateTimeFromSelects(picker);
                        }
                    });
                },
                toggle(picker) {
                    if (picker.classList.contains("ux-date-range-picker-open")) this.hide(picker);
                    else this.show(picker);
                },
                show(picker) {
                    this.hideAll();
                    picker.classList.add("ux-date-range-picker-open");
                    let dropdown = picker.querySelector(".ux-date-range-picker-dropdown");
                    if (!dropdown) {
                        this.createDropdown(picker);
                        dropdown = picker.querySelector(".ux-date-range-picker-dropdown");
                    }
                    if (dropdown) dropdown.classList.add("show");
                    this.setViewMonths(picker);
                    this.renderCalendars(picker);
                    if (this.isShowTime(picker)) this.populateTimeSelects(picker);
                },
                hide(picker) {
                    picker.classList.remove("ux-date-range-picker-open");
                    const dropdown = picker.querySelector(".ux-date-range-picker-dropdown");
                    if (dropdown) dropdown.classList.remove("show");
                    this.selecting = null;
                },
                hideAll() {
                    document.querySelectorAll(".ux-date-range-picker-open").forEach(picker => this.hide(picker));
                },
                createDropdown(picker) {
                    const dropdown = document.createElement("div");
                    dropdown.className = "ux-date-range-picker-dropdown";
                    const calendars = document.createElement("div");
                    calendars.className = "ux-date-range-picker-calendars";
                    const leftCal = document.createElement("div");
                    leftCal.className = "ux-date-range-picker-calendar";
                    leftCal.dataset.calendar = "0";
                    leftCal.innerHTML = this.buildCalendarHTML("left");
                    const rightCal = document.createElement("div");
                    rightCal.className = "ux-date-range-picker-calendar";
                    rightCal.dataset.calendar = "1";
                    rightCal.innerHTML = this.buildCalendarHTML("right");
                    calendars.appendChild(leftCal);
                    calendars.appendChild(rightCal);
                    dropdown.appendChild(calendars);
                    const footer = document.createElement("div");
                    footer.className = "ux-date-range-picker-footer";
                    let footerHTML = "";
                    if (this.isShowTime(picker)) {
                        footerHTML += "<div class=\"ux-date-range-picker-time\">" +
                            "<select class=\"ux-date-range-picker-time-hour\"></select>" +
                            "<span class=\"ux-date-range-picker-time-sep\">:</span>" +
                            "<select class=\"ux-date-range-picker-time-minute\"></select>" +
                            "<span class=\"ux-date-range-picker-time-sep\">:</span>" +
                            "<select class=\"ux-date-range-picker-time-second\"></select>" +
                            "</div>";
                        footerHTML += "<div class=\"ux-date-range-picker-actions\">" +
                            "<button class=\"ux-date-range-picker-confirm-btn\" type=\"button\">确定</button>" +
                            "</div>";
                    }
                    footer.innerHTML = footerHTML;
                    dropdown.appendChild(footer);
                    picker.appendChild(dropdown);
                    dropdown.querySelector(".ux-date-range-picker-confirm-btn")?.addEventListener("click", (e) => {
                        e.stopPropagation();
                        this.confirm(picker);
                    });
                },
                buildCalendarHTML(side) {
                    return `
                        <div class="ux-date-range-picker-calendar-header">
                            <button class="ux-date-range-picker-nav-btn" data-ux-action="prev-year" data-calendar="${side === "left" ? "0" : "1"}"><i class="bi bi-chevron-double-left"></i></button>
                            <button class="ux-date-range-picker-nav-btn" data-ux-action="prev-month" data-calendar="${side === "left" ? "0" : "1"}"><i class="bi bi-chevron-left"></i></button>
                            <span class="ux-date-range-picker-calendar-title"></span>
                            <button class="ux-date-range-picker-nav-btn" data-ux-action="next-month" data-calendar="${side === "left" ? "0" : "1"}"><i class="bi bi-chevron-right"></i></button>
                            <button class="ux-date-range-picker-nav-btn" data-ux-action="next-year" data-calendar="${side === "left" ? "0" : "1"}"><i class="bi bi-chevron-double-right"></i></button>
                        </div>
                        <div class="ux-date-range-picker-weekdays">
                            <span class="ux-date-range-picker-weekday">日</span>
                            <span class="ux-date-range-picker-weekday">一</span>
                            <span class="ux-date-range-picker-weekday">二</span>
                            <span class="ux-date-range-picker-weekday">三</span>
                            <span class="ux-date-range-picker-weekday">四</span>
                            <span class="ux-date-range-picker-weekday">五</span>
                            <span class="ux-date-range-picker-weekday">六</span>
                        </div>
                        <div class="ux-date-range-picker-days"></div>
                    `;
                },
                setViewMonths(picker) {
                    const now = new Date();
                    if (picker.dataset.dateStart) {
                        const parts = picker.dataset.dateStart.split("-");
                        picker.dataset.uxLeftViewYear = parts[0];
                        picker.dataset.uxLeftViewMonth = parts[1];
                    } else {
                        picker.dataset.uxLeftViewYear = String(now.getFullYear());
                        picker.dataset.uxLeftViewMonth = String(now.getMonth() + 1);
                    }
                    let rightMonth = parseInt(picker.dataset.uxLeftViewMonth, 10) + 1;
                    let rightYear = parseInt(picker.dataset.uxLeftViewYear, 10);
                    if (rightMonth > 12) { rightMonth = 1; rightYear++; }
                    picker.dataset.uxRightViewYear = String(rightYear);
                    picker.dataset.uxRightViewMonth = String(rightMonth);
                },
                getViewMonths(picker) {
                    const leftYear = parseInt(picker.dataset.uxLeftViewYear, 10) || new Date().getFullYear();
                    const leftMonth = parseInt(picker.dataset.uxLeftViewMonth, 10) || (new Date().getMonth() + 1);
                    let rightMonth = leftMonth + 1;
                    let rightYear = leftYear;
                    if (rightMonth > 12) { rightMonth = 1; rightYear++; }
                    return { left: { year: leftYear, month: leftMonth }, right: { year: rightYear, month: rightMonth } };
                },
                renderCalendars(picker) {
                    const views = this.getViewMonths(picker);
                    this.renderOneCalendar(picker, 0, views.left.year, views.left.month);
                    this.renderOneCalendar(picker, 1, views.right.year, views.right.month);
                },
                renderOneCalendar(picker, calIdx, year, month) {
                    if (isNaN(year) || isNaN(month) || year < 1 || month < 1 || month > 12) {
                        const now = new Date();
                        year = now.getFullYear();
                        month = now.getMonth() + 1;
                    }
                    const calendars = picker.querySelectorAll(".ux-date-range-picker-calendar");
                    const cal = calendars[calIdx];
                    if (!cal) return;
                    const title = cal.querySelector(".ux-date-range-picker-calendar-title");
                    if (title) title.textContent = `${year}年${month}月`;
                    const daysContainer = cal.querySelector(".ux-date-range-picker-days");
                    if (!daysContainer) return;
                    const today = this.formatDate(new Date());
                    const startDate = picker.dataset.dateStart || "";
                    const endDate = picker.dataset.dateEnd || "";
                    const minDate = picker.dataset.dateMin || null;
                    const maxDate = picker.dataset.dateMax || null;
                    const firstDay = new Date(year, month - 1, 1);
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const startWeekday = firstDay.getDay();
                    const prevMonthDays = new Date(year, month - 1, 0).getDate();
                    let html = "";
                    for (let i = startWeekday - 1; i >= 0; i--) {
                        const day = prevMonthDays - i;
                        html += `<button class="ux-date-range-picker-day other-month" data-date="" disabled>${day}</button>`;
                    }
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dateStr = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                        let classes = "ux-date-range-picker-day";
                        if (dateStr === today) classes += " today";
                        if (startDate && dateStr === startDate) classes += " range-start";
                        if (endDate && dateStr === endDate) classes += " range-end";
                        if (startDate && endDate && dateStr > startDate && dateStr < endDate) classes += " in-range";
                        const disabled = (minDate && dateStr < minDate) || (maxDate && dateStr > maxDate);
                        if (disabled) classes += " disabled";
                        html += `<button class="${classes}" data-date="${dateStr}"${disabled ? " disabled" : ""}>${day}</button>`;
                    }
                    const remaining = 42 - (startWeekday + daysInMonth);
                    for (let day = 1; day <= remaining; day++) {
                        html += `<button class="ux-date-range-picker-day other-month" data-date="" disabled>${day}</button>`;
                    }
                    daysContainer.innerHTML = html;
                },
                selectDate(picker, date) {
                    const startDate = picker.dataset.dateStart;
                    const endDate = picker.dataset.dateEnd;
                    const minDate = picker.dataset.dateMin;
                    const maxDate = picker.dataset.dateMax;
                    if (minDate && date < minDate) return;
                    if (maxDate && date > maxDate) return;
                    if (!startDate || (startDate && endDate)) {
                        picker.dataset.dateStart = date;
                        picker.dataset.dateEnd = "";
                    } else {
                        if (date < startDate) {
                            picker.dataset.dateEnd = startDate;
                            picker.dataset.dateStart = date;
                        } else {
                            picker.dataset.dateEnd = date;
                        }
                        this.updateInput(picker);
                        if (!this.isShowTime(picker)) {
                            this.confirm(picker);
                        }
                    }
                    this.renderCalendars(picker);
                },
                selectToday(picker) {
                    const today = this.formatDate(new Date());
                    picker.dataset.dateStart = today;
                    picker.dataset.dateEnd = today;
                    this.updateInput(picker);
                    this.renderCalendars(picker);
                    if (!this.isShowTime(picker)) {
                        this.confirm(picker);
                    }
                },
                confirm(picker) {
                    const startValue = picker.dataset.dateStart;
                    const endValue = picker.dataset.dateEnd;
                    if (!startValue || !endValue) return;
                    const separator = picker.dataset.dateSeparator || "~";
                    let displayValue = `${startValue} ${separator} ${endValue}`;
                    if (this.isShowTime(picker)) {
                        const hour = picker.querySelector(".ux-date-range-picker-time-hour")?.value || "00";
                        const minute = picker.querySelector(".ux-date-range-picker-time-minute")?.value || "00";
                        const second = picker.querySelector(".ux-date-range-picker-time-second")?.value || "00";
                        const timeStr = ` ${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}:${String(second).padStart(2, "0")}`;
                        const startParts = startValue.split(" ");
                        const endParts = endValue.split(" ");
                        const fullStart = startParts[0] + timeStr;
                        const fullEnd = endParts[0] + timeStr;
                        displayValue = `${fullStart} ${separator} ${fullEnd}`;
                        picker.dataset.dateStart = fullStart;
                        picker.dataset.dateEnd = fullEnd;
                        this.updateInput(picker);
                        picker.dispatchEvent(new CustomEvent("ux:change", { detail: { start: fullStart, end: fullEnd, value: displayValue }, bubbles: true }));
                    } else {
                        const input = picker.querySelector(".ux-date-range-picker-input");
                        if (input) input.value = displayValue;
                        picker.dispatchEvent(new CustomEvent("ux:change", { detail: { start: startValue, end: endValue, value: displayValue }, bubbles: true }));
                    }
                    this.hide(picker);
                },
                clear(picker) {
                    const input = picker.querySelector(".ux-date-range-picker-input");
                    if (input) input.value = "";
                    picker.dataset.dateStart = "";
                    picker.dataset.dateEnd = "";
                    this.selecting = null;
                    this.renderCalendars(picker);
                    picker.dispatchEvent(new CustomEvent("ux:change", { detail: { value: "" }, bubbles: true }));
                },
                updateInput(picker) {
                    const input = picker.querySelector(".ux-date-range-picker-input");
                    if (!input) return;
                    const start = picker.dataset.dateStart;
                    const end = picker.dataset.dateEnd;
                    const separator = picker.dataset.dateSeparator || "~";
                    if (start && end) {
                        input.value = `${start} ${separator} ${end}`;
                    } else if (start) {
                        input.value = start;
                    } else {
                        input.value = "";
                    }
                },
                handleNav(picker, action, calIdx) {
                    const side = calIdx === 0 ? "uxLeft" : "uxRight";
                    const year = parseInt(picker.dataset[side + "ViewYear"], 10);
                    const month = parseInt(picker.dataset[side + "ViewMonth"], 10);
                    let newYear = year;
                    let newMonth = month;
                    switch (action) {
                        case "prev-year": newYear--; break;
                        case "prev-month": newMonth--; if (newMonth < 1) { newMonth = 12; newYear--; } break;
                        case "next-month": newMonth++; if (newMonth > 12) { newMonth = 1; newYear++; } break;
                        case "next-year": newYear++; break;
                    }
                    picker.dataset[side + "ViewYear"] = String(newYear);
                    picker.dataset[side + "ViewMonth"] = String(newMonth);
                    this.renderOneCalendar(picker, calIdx, newYear, newMonth);
                },
                populateTimeSelects(picker) {
                    const hourSelect = picker.querySelector(".ux-date-range-picker-time-hour");
                    const minuteSelect = picker.querySelector(".ux-date-range-picker-time-minute");
                    const secondSelect = picker.querySelector(".ux-date-range-picker-time-second");
                    if (hourSelect && hourSelect.options.length === 0) {
                        for (let h = 0; h < 24; h++) { const opt = document.createElement("option"); opt.value = String(h); opt.textContent = String(h).padStart(2, "0"); hourSelect.appendChild(opt); }
                    }
                    if (minuteSelect && minuteSelect.options.length === 0) {
                        for (let m = 0; m < 60; m++) { const opt = document.createElement("option"); opt.value = String(m); opt.textContent = String(m).padStart(2, "0"); minuteSelect.appendChild(opt); }
                    }
                    if (secondSelect && secondSelect.options.length === 0) {
                        for (let s = 0; s < 60; s++) { const opt = document.createElement("option"); opt.value = String(s); opt.textContent = String(s).padStart(2, "0"); secondSelect.appendChild(opt); }
                    }
                    const now = new Date();
                    if (hourSelect) hourSelect.value = String(now.getHours());
                    if (minuteSelect) minuteSelect.value = String(now.getMinutes());
                    if (secondSelect) secondSelect.value = String(now.getSeconds());
                },
                updateTimeFromSelects(picker) {
                },
                isShowTime(picker) { return picker.dataset.showTime === "true"; },
                formatDate(date) {
                    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
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
                }
            };
            return DateRangePicker;
        ');
    }

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
