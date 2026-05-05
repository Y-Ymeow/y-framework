<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 日期选择器
 *
 * 支持日期选择和日期时间选择，内置日历面板、月份导航、范围限制。
 * 启用 showTime() 后自动切换为日期时间模式，需点"确定"关闭面板。
 *
 * ## JS 交互能力（datePicker.js）
 *
 * PHP 定义配置 → JS 自动处理日历渲染、日期选择、面板开关：
 *
 * - **初始化**: 扫描 `.ux-datepicker` 元素，绑定点击事件
 * - **面板控制**: 点击输入框 → 显示日历面板 → 选择日期 → 更新值
 * - **导航**: 上月/下月按钮切换视图
 * - **时间选择**: showTime 模式下显示时分秒选择器
 * - **值同步**: 选择后触发 `ux:change` 事件 → liveModel 同步到 LiveComponent
 *
 * ### 数据格式
 * - 日期模式: `Y-m-d`（如 "2026-05-02"）
 * - 时间模式: `Y-m-d H:i:s`（如 "2026-05-02 14:30:00"）
 *
 * @ux-category Form
 * @ux-since 1.0.0
 *
 * @ux-example
 * // 基础日期选择
 * DatePicker::make()->placeholder('选择日期')
 *
 * // 日期时间选择
 * DatePicker::make()
 *     ->showTime()
 *     ->timeHour(9)
 *     ->timeMinute(0)
 *     ->liveModel('eventDate')
 *
 * // 范围限制
 * DatePicker::make()
 *     ->minDate('2026-01-01')
 *     ->maxDate('2026-12-31')
 * @ux-example-end
 */
class DatePicker extends UXComponent
{
    protected static ?string $componentName = 'datePicker';

    protected ?string $value = null;
    protected ?string $placeholder = null;
    protected ?string $format = 'Y-m-d';
    protected bool $disabled = false;
    protected bool $allowClear = true;
    protected bool $showToday = true;
    protected bool $showTime = false;
    protected ?string $minDate = null;
    protected ?string $maxDate = null;
    protected ?string $action = null;

    protected function init(): void
    {
        $this->registerJs('datePicker', '
            const DatePicker = {
                init() {
                    // 初始化所有日期选择器：设置默认值和初始视图
                    document.querySelectorAll(".ux-date-picker").forEach(picker => {
                        const view = this.getViewMonth(picker);
                        picker.dataset.uxViewYear = String(view.year);
                        picker.dataset.uxViewMonth = String(view.month);
                        // 如果有默认值，同步到 input
                        const dateValue = picker.dataset.dateValue;
                        if (dateValue) {
                            const input = picker.querySelector(".ux-date-picker-input");
                            if (input && !input.value) input.value = dateValue;
                        }
                    });

                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const picker = e.target.closest(".ux-date-picker");
                        if (!picker) { this.hideAll(); return; }
                        const clear = e.target.closest(".ux-date-picker-clear");
                        if (clear) { e.stopPropagation(); this.clear(picker); return; }
                        const day = e.target.closest(".ux-date-picker-day");
                        if (day) { e.stopPropagation(); const date = day.dataset.date; if (date) this.selectDate(picker, date); return; }
                        const navBtn = e.target.closest(".ux-date-picker-nav-btn");
                        if (navBtn) { e.stopPropagation(); const action = navBtn.dataset.uxAction; if (action) this.handleNav(picker, action); return; }
                        const todayBtn = e.target.closest(".ux-date-picker-today-btn");
                        if (todayBtn) { e.stopPropagation(); this.selectToday(picker); return; }
                        const confirmBtn = e.target.closest(".ux-date-picker-confirm-btn");
                        if (confirmBtn) { e.stopPropagation(); this.confirm(picker); return; }
                        const inputWrapper = e.target.closest(".ux-date-picker-input-wrapper");
                        if (inputWrapper) { this.toggle(picker); return; }
                    });
                    document.addEventListener("change", (e) => {
                        if (!e.target || !e.target.classList) return;
                        if (e.target.classList.contains("ux-date-picker-time-hour") || e.target.classList.contains("ux-date-picker-time-minute") || e.target.classList.contains("ux-date-picker-time-second")) {
                            const picker = e.target.closest(".ux-date-picker");
                            if (picker) this.updateTimeFromSelects(picker);
                        }
                    });
                },
                parseDate(str) {
                    if (!str || typeof str !== "string") return null;
                    const dateTime = str.split(" ");
                    const datePart = dateTime[0];
                    const timePart = dateTime[1] || null;
                    const parts = datePart.split("-");
                    if (parts.length !== 3) return null;
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    const d = parseInt(parts[2], 10);
                    if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
                    const result = { year: y, month: m, day: d };
                    if (timePart) {
                        const tp = timePart.split(":");
                        result.hour = parseInt(tp[0], 10) || 0;
                        result.minute = parseInt(tp[1], 10) || 0;
                        result.second = parseInt(tp[2], 10) || 0;
                    }
                    return result;
                },
                getViewMonth(picker) {
                    const vy = parseInt(picker.dataset.uxViewYear, 10);
                    const vm = parseInt(picker.dataset.uxViewMonth, 10);
                    if (!isNaN(vy) && !isNaN(vm) && vy > 0 && vm >= 1 && vm <= 12) return { year: vy, month: vm };
                    const val = picker.dataset.dateValue;
                    const parsed = this.parseDate(val);
                    if (parsed) return { year: parsed.year, month: parsed.month };
                    const now = new Date();
                    return { year: now.getFullYear(), month: now.getMonth() + 1 };
                },
                isShowTime(picker) { return picker.dataset.showTime === "true"; },
                getTimeValues(picker) {
                    return { hour: parseInt(picker.dataset.timeHour, 10) || 0, minute: parseInt(picker.dataset.timeMinute, 10) || 0, second: parseInt(picker.dataset.timeSecond, 10) || 0 };
                },
                setTimeValues(picker, hour, minute, second) {
                    picker.dataset.timeHour = String(hour);
                    picker.dataset.timeMinute = String(minute);
                    picker.dataset.timeSecond = String(second);
                },
                toggle(picker) {
                    if (picker.classList.contains("ux-date-picker-open")) this.hide(picker);
                    else this.show(picker);
                },
                show(picker) {
                    this.hideAll();
                    picker.classList.add("ux-date-picker-open");
                    const dropdown = picker.querySelector(".ux-date-picker-dropdown");
                    if (dropdown) dropdown.classList.add("show");
                    const view = this.getViewMonth(picker);
                    picker.dataset.uxViewYear = String(view.year);
                    picker.dataset.uxViewMonth = String(view.month);
                    this.renderMonth(picker, view.year, view.month);
                    if (this.isShowTime(picker)) this.populateTimeSelects(picker);
                },
                hide(picker) {
                    picker.classList.remove("ux-date-picker-open");
                    const dropdown = picker.querySelector(".ux-date-picker-dropdown");
                    if (dropdown) dropdown.classList.remove("show");
                },
                hideAll() {
                    document.querySelectorAll(".ux-date-picker-open").forEach(picker => this.hide(picker));
                },
                selectDate(picker, date) {
                    const minDate = picker.dataset.dateMin;
                    const maxDate = picker.dataset.dateMax;
                    if (minDate && date < minDate) return;
                    if (maxDate && date > maxDate) return;
                    picker.dataset.dateValue = date;
                    picker.querySelectorAll(".ux-date-picker-day").forEach(day => day.classList.toggle("selected", day.dataset.date === date));
                    if (this.isShowTime(picker)) {
                        const time = this.getTimeValues(picker);
                        const fullValue = `${date} ${String(time.hour).padStart(2, "0")}:${String(time.minute).padStart(2, "0")}:${String(time.second).padStart(2, "0")}`;
                        const input = picker.querySelector(".ux-date-picker-input");
                        if (input) input.value = fullValue;
                    } else {
                        const input = picker.querySelector(".ux-date-picker-input");
                        if (input) input.value = date;
                        this.hide(picker);
                        picker.dispatchEvent(new CustomEvent("ux:change", { detail: { value: date }, bubbles: true }));
                    }
                },
                selectToday(picker) {
                    const today = this.formatDate(new Date());
                    this.selectDate(picker, today);
                    if (this.isShowTime(picker)) {
                        const now = new Date();
                        this.setTimeValues(picker, now.getHours(), now.getMinutes(), now.getSeconds());
                        this.populateTimeSelects(picker);
                    }
                },
                confirm(picker) {
                    const dateValue = picker.dataset.dateValue;
                    if (!dateValue) return;
                    if (this.isShowTime(picker)) {
                        const time = this.getTimeValues(picker);
                        const fullValue = `${dateValue} ${String(time.hour).padStart(2, "0")}:${String(time.minute).padStart(2, "0")}:${String(time.second).padStart(2, "0")}`;
                        const input = picker.querySelector(".ux-date-picker-input");
                        if (input) input.value = fullValue;
                        picker.dispatchEvent(new CustomEvent("ux:change", { detail: { value: fullValue }, bubbles: true }));
                    } else {
                        picker.dispatchEvent(new CustomEvent("ux:change", { detail: { value: dateValue }, bubbles: true }));
                    }
                    this.hide(picker);
                },
                clear(picker) {
                    const input = picker.querySelector(".ux-date-picker-input");
                    if (input) input.value = "";
                    picker.dataset.dateValue = "";
                    picker.querySelectorAll(".ux-date-picker-day").forEach(day => day.classList.remove("selected"));
                    picker.dispatchEvent(new CustomEvent("ux:change", { detail: { value: "" }, bubbles: true }));
                },
                handleNav(picker, action) {
                    const view = this.getViewMonth(picker);
                    let year = view.year;
                    let month = view.month;
                    switch (action) {
                        case "prev-year": year--; break;
                        case "prev-month": month--; if (month < 1) { month = 12; year--; } break;
                        case "next-month": month++; if (month > 12) { month = 1; year++; } break;
                        case "next-year": year++; break;
                    }
                    picker.dataset.uxViewYear = String(year);
                    picker.dataset.uxViewMonth = String(month);
                    this.renderMonth(picker, year, month);
                },
                renderMonth(picker, year, month) {
                    if (isNaN(year) || isNaN(month) || year < 1 || month < 1 || month > 12) {
                        const now = new Date();
                        year = now.getFullYear();
                        month = now.getMonth() + 1;
                    }
                    const today = this.formatDate(new Date());
                    const selectedDate = picker.dataset.dateValue ? picker.dataset.dateValue.split(" ")[0] : "";
                    const minDate = picker.dataset.dateMin || null;
                    const maxDate = picker.dataset.dateMax || null;
                    const title = picker.querySelector(".ux-date-picker-title");
                    if (title) title.textContent = `${year}年${month}月`;
                    picker.dataset.uxViewYear = String(year);
                    picker.dataset.uxViewMonth = String(month);
                    const daysGrid = picker.querySelector(".ux-date-picker-days");
                    if (!daysGrid) return;
                    const firstDay = new Date(year, month - 1, 1);
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const startWeekday = firstDay.getDay();
                    const prevMonthDays = new Date(year, month - 1, 0).getDate();
                    let html = "";
                    for (let i = startWeekday - 1; i >= 0; i--) {
                        const day = prevMonthDays - i;
                        const pm = month - 1;
                        const py = pm < 1 ? year - 1 : year;
                        const rm = pm < 1 ? 12 : pm;
                        const dateStr = `${py}-${String(rm).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                        const disabled = this.isOutOfRange(dateStr, minDate, maxDate);
                        html += `<button class="ux-date-picker-day other-month${disabled ? " disabled" : ""}" data-date="${dateStr}"${disabled ? " disabled" : ""}>${day}</button>`;
                    }
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dateStr = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                        let classes = "ux-date-picker-day";
                        if (dateStr === today) classes += " today";
                        if (dateStr === selectedDate) classes += " selected";
                        const disabled = this.isOutOfRange(dateStr, minDate, maxDate);
                        if (disabled) classes += " disabled";
                        html += `<button class="${classes}" data-date="${dateStr}"${disabled ? " disabled" : ""}>${day}</button>`;
                    }
                    const remainingDays = 42 - (startWeekday + daysInMonth);
                    for (let day = 1; day <= remainingDays; day++) {
                        const nm = month + 1;
                        const ny = nm > 12 ? year + 1 : year;
                        const rn = nm > 12 ? 1 : nm;
                        const dateStr = `${ny}-${String(rn).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                        const disabled = this.isOutOfRange(dateStr, minDate, maxDate);
                        html += `<button class="ux-date-picker-day other-month${disabled ? " disabled" : ""}" data-date="${dateStr}"${disabled ? " disabled" : ""}>${day}</button>`;
                    }
                    daysGrid.innerHTML = html;
                },
                populateTimeSelects(picker) {
                    const time = this.getTimeValues(picker);
                    const hourSelect = picker.querySelector(".ux-date-picker-time-hour");
                    const minuteSelect = picker.querySelector(".ux-date-picker-time-minute");
                    const secondSelect = picker.querySelector(".ux-date-picker-time-second");
                    if (hourSelect && hourSelect.options.length === 0) {
                        for (let h = 0; h < 24; h++) { const opt = document.createElement("option"); opt.value = String(h); opt.textContent = String(h).padStart(2, "0"); hourSelect.appendChild(opt); }
                    }
                    if (minuteSelect && minuteSelect.options.length === 0) {
                        for (let m = 0; m < 60; m++) { const opt = document.createElement("option"); opt.value = String(m); opt.textContent = String(m).padStart(2, "0"); minuteSelect.appendChild(opt); }
                    }
                    if (secondSelect && secondSelect.options.length === 0) {
                        for (let s = 0; s < 60; s++) { const opt = document.createElement("option"); opt.value = String(s); opt.textContent = String(s).padStart(2, "0"); secondSelect.appendChild(opt); }
                    }
                    if (hourSelect) hourSelect.value = String(time.hour);
                    if (minuteSelect) minuteSelect.value = String(time.minute);
                    if (secondSelect) secondSelect.value = String(time.second);
                },
                updateTimeFromSelects(picker) {
                    const hourSelect = picker.querySelector(".ux-date-picker-time-hour");
                    const minuteSelect = picker.querySelector(".ux-date-picker-time-minute");
                    const secondSelect = picker.querySelector(".ux-date-picker-time-second");
                    const hour = hourSelect ? parseInt(hourSelect.value, 10) : 0;
                    const minute = minuteSelect ? parseInt(minuteSelect.value, 10) : 0;
                    const second = secondSelect ? parseInt(secondSelect.value, 10) : 0;
                    this.setTimeValues(picker, hour, minute, second);
                    const dateValue = picker.dataset.dateValue;
                    if (dateValue) {
                        const datePart = dateValue.split(" ")[0];
                        const fullValue = `${datePart} ${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}:${String(second).padStart(2, "0")}`;
                        const input = picker.querySelector(".ux-date-picker-input");
                        if (input) input.value = fullValue;
                    }
                },
                isOutOfRange(dateStr, minDate, maxDate) {
                    if (minDate && dateStr < minDate) return true;
                    if (maxDate && dateStr > maxDate) return true;
                    return false;
                },
                formatDate(date) {
                    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
                },
                setValue(picker, date) {
                    if (!date) return;
                    picker.dataset.dateValue = date;
                    const input = picker.querySelector(".ux-date-picker-input");
                    if (input) input.value = date;
                    const parsed = this.parseDate(date);
                    if (parsed && parsed.hour !== undefined) {
                        this.setTimeValues(picker, parsed.hour, parsed.minute, parsed.second);
                    }
                }
            };
            return DatePicker;
        ');
    }

    protected int $timeHour = 0;
    protected int $timeMinute = 0;
    protected int $timeSecond = 0;

    /**
     * 设置默认日期值
     * @param string $value 日期字符串，格式 Y-m-d
     * @return static
     * @ux-example DatePicker::make()->value('2026-05-02')
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example DatePicker::make()->placeholder('请选择日期')
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
     * @ux-example DatePicker::make()->format('Y/m/d')
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
     * @ux-example DatePicker::make()->disabled()
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
     * @ux-example DatePicker::make()->allowClear(false)
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
     * @ux-example DatePicker::make()->showToday()
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
     * @ux-example DatePicker::make()->minDate('2026-01-01')
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
     * @ux-example DatePicker::make()->maxDate('2026-12-31')
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
     * @ux-example DatePicker::make()->action('updateDate')
     * @ux-internal
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 启用时间选择，格式自动切换为 Y-m-d H:i:s
     * @param bool $show 是否启用
     * @return static
     * @ux-example DatePicker::make()->showTime()
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

    /**
     * 设置默认小时
     * @param int $hour 小时 0-23
     * @return static
     * @ux-example DatePicker::make()->showTime()->timeHour(9)
     */
    public function timeHour(int $hour): static
    {
        $this->timeHour = max(0, min(23, $hour));
        return $this;
    }

    /**
     * 设置默认分钟
     * @param int $minute 分钟 0-59
     * @return static
     * @ux-example DatePicker::make()->showTime()->timeMinute(30)
     */
    public function timeMinute(int $minute): static
    {
        $this->timeMinute = max(0, min(59, $minute));
        return $this;
    }

    /**
     * 设置默认秒数
     * @param int $second 秒 0-59
     * @return static
     * @ux-example DatePicker::make()->showTime()->timeSecond(0)
     */
    public function timeSecond(int $second): static
    {
        $this->timeSecond = max(0, min(59, $second));
        return $this;
    }

    /**
     * @ux-internal
     */
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
