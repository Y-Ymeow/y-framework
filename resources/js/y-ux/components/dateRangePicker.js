// DateRangePicker 日期范围选择器组件
const DateRangePicker = {
    init() {
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const picker = e.target.closest('.ux-date-range-picker');
            if (!picker) {
                this.hideAll();
                return;
            }

            const clear = e.target.closest('.ux-date-range-picker-clear');
            if (clear) {
                e.stopPropagation();
                this.clear(picker);
                return;
            }

            const day = e.target.closest('.ux-date-range-picker-day');
            if (day) {
                e.stopPropagation();
                const date = day.dataset.date;
                if (date) {
                    this.selectDate(picker, date);
                }
                return;
            }

            const navBtn = e.target.closest('.ux-date-range-picker-nav-btn');
            if (navBtn) {
                e.stopPropagation();
                const action = navBtn.dataset.uxAction;
                const calendarIdx = parseInt(navBtn.dataset.calendar || '0', 10);
                if (action) {
                    this.handleNav(picker, action, calendarIdx);
                }
                return;
            }

            const inputWrapper = e.target.closest('.ux-date-range-picker-input-wrapper');
            if (inputWrapper) {
                this.toggle(picker);
                return;
            }
        });
    },

    parseDate(str) {
        if (!str || typeof str !== 'string') return null;
        const parts = str.split('-');
        if (parts.length !== 3) return null;
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const d = parseInt(parts[2], 10);
        if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
        return { year: y, month: m, day: d };
    },

    formatDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    },

    getViewMonths(picker) {
        const leftView = this.getViewMonth(picker, 'left');
        let rightYear = leftView.year;
        let rightMonth = leftView.month + 1;
        if (rightMonth > 12) { rightMonth = 1; rightYear++; }
        return { left: leftView, right: { year: rightYear, month: rightMonth } };
    },

    getViewMonth(picker, side) {
        const prefix = side === 'left' ? 'uxLeft' : 'uxRight';
        const vy = parseInt(picker.dataset[prefix + 'ViewYear'], 10);
        const vm = parseInt(picker.dataset[prefix + 'ViewMonth'], 10);
        if (!isNaN(vy) && !isNaN(vm) && vy > 0 && vm >= 1 && vm <= 12) {
            return { year: vy, month: vm };
        }
        const now = new Date();
        if (side === 'left') {
            return { year: now.getFullYear(), month: now.getMonth() + 1 };
        } else {
            let y = now.getFullYear();
            let m = now.getMonth() + 2;
            if (m > 12) { m = 1; y++; }
            return { year: y, month: m };
        }
    },

    isShowTime(picker) {
        return picker.dataset.showTime === 'true';
    },

    toggle(picker) {
        if (picker.classList.contains('ux-date-range-picker-open')) {
            this.hide(picker);
        } else {
            this.show(picker);
        }
    },

    show(picker) {
        this.hideAll();
        picker.classList.add('ux-date-range-picker-open');

        const dropdown = picker.querySelector('.ux-date-range-picker-dropdown');
        if (!dropdown) {
            this.createDropdown(picker);
        }

        const dd = picker.querySelector('.ux-date-range-picker-dropdown');
        if (dd) dd.classList.add('show');

        const views = this.getViewMonths(picker);
        picker.dataset.uxLeftViewYear = String(views.left.year);
        picker.dataset.uxLeftViewMonth = String(views.left.month);
        picker.dataset.uxRightViewYear = String(views.right.year);
        picker.dataset.uxRightViewMonth = String(views.right.month);
        this.renderCalendars(picker);

        if (this.isShowTime(picker)) {
            this.populateTimeSelects(picker);
        }
    },

    hide(picker) {
        picker.classList.remove('ux-date-range-picker-open');
        const dropdown = picker.querySelector('.ux-date-range-picker-dropdown');
        if (dropdown) dropdown.classList.remove('show');
    },

    hideAll() {
        document.querySelectorAll('.ux-date-range-picker-open').forEach(picker => {
            this.hide(picker);
        });
    },

    createDropdown(picker) {
        const dropdown = document.createElement('div');
        dropdown.className = 'ux-date-range-picker-dropdown';

        const calendars = document.createElement('div');
        calendars.className = 'ux-date-range-picker-calendars';

        const leftCal = document.createElement('div');
        leftCal.className = 'ux-date-range-picker-calendar';
        leftCal.dataset.calendar = '0';
        leftCal.innerHTML = this.buildCalendarHTML('left');

        const rightCal = document.createElement('div');
        rightCal.className = 'ux-date-range-picker-calendar';
        rightCal.dataset.calendar = '1';
        rightCal.innerHTML = this.buildCalendarHTML('right');

        calendars.appendChild(leftCal);
        calendars.appendChild(rightCal);
        dropdown.appendChild(calendars);

        const footer = document.createElement('div');
        footer.className = 'ux-date-range-picker-footer';
        let footerHTML = '<div class="ux-date-range-picker-presets">' +
            '<span class="ux-date-range-picker-preset" data-preset="week">最近一周</span>' +
            '<span class="ux-date-range-picker-preset" data-preset="month">最近一月</span>' +
            '<span class="ux-date-range-picker-preset" data-preset="quarter">最近三月</span>' +
            '</div>';

        if (this.isShowTime(picker)) {
            footerHTML += '<div class="ux-date-range-picker-time">' +
                '<select class="ux-date-range-picker-time-hour"></select>' +
                '<span class="ux-date-range-picker-time-sep">:</span>' +
                '<select class="ux-date-range-picker-time-minute"></select>' +
                '<span class="ux-date-range-picker-time-sep">:</span>' +
                '<select class="ux-date-range-picker-time-second"></select>' +
                '</div>';
        }

        footerHTML += '<div class="ux-date-range-picker-actions">' +
            '<button class="ux-date-range-picker-clear-btn" type="button">清除</button>' +
            (this.isShowTime(picker) ? '<button class="ux-date-range-picker-confirm-btn" type="button">确定</button>' : '') +
            '</div>';

        footer.innerHTML = footerHTML;
        dropdown.appendChild(footer);

        picker.appendChild(dropdown);

        // 预设按钮事件
        dropdown.querySelectorAll('.ux-date-range-picker-preset').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectPreset(picker, btn.dataset.preset);
            });
        });

        dropdown.querySelector('.ux-date-range-picker-clear-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.clear(picker);
        });

        dropdown.querySelector('.ux-date-range-picker-confirm-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.confirm(picker);
        });
    },

    buildCalendarHTML(side) {
        return `
            <div class="ux-date-range-picker-calendar-header">
                <button class="ux-date-range-picker-nav-btn" data-ux-action="prev-year" data-calendar="${side === 'left' ? '0' : '1'}"><i class="bi bi-chevron-double-left"></i></button>
                <button class="ux-date-range-picker-nav-btn" data-ux-action="prev-month" data-calendar="${side === 'left' ? '0' : '1'}"><i class="bi bi-chevron-left"></i></button>
                <span class="ux-date-range-picker-calendar-title"></span>
                <button class="ux-date-range-picker-nav-btn" data-ux-action="next-month" data-calendar="${side === 'left' ? '0' : '1'}"><i class="bi bi-chevron-right"></i></button>
                <button class="ux-date-range-picker-nav-btn" data-ux-action="next-year" data-calendar="${side === 'left' ? '0' : '1'}"><i class="bi bi-chevron-double-right"></i></button>
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

        const calendars = picker.querySelectorAll('.ux-date-range-picker-calendar');
        const cal = calendars[calIdx];
        if (!cal) return;

        const title = cal.querySelector('.ux-date-range-picker-calendar-title');
        if (title) title.textContent = `${year}年${month}月`;

        const daysContainer = cal.querySelector('.ux-date-range-picker-days');
        if (!daysContainer) return;

        const today = this.formatDate(new Date());
        const startDate = picker.dataset.dateStart || '';
        const endDate = picker.dataset.dateEnd || '';
        const minDate = picker.dataset.dateMin || null;
        const maxDate = picker.dataset.dateMax || null;

        const firstDay = new Date(year, month - 1, 1);
        const daysInMonth = new Date(year, month, 0).getDate();
        const startWeekday = firstDay.getDay();
        const prevMonthDays = new Date(year, month - 1, 0).getDate();

        let html = '';

        for (let i = startWeekday - 1; i >= 0; i--) {
            const day = prevMonthDays - i;
            html += `<button class="ux-date-range-picker-day other-month" data-date="" disabled>${day}</button>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            let classes = 'ux-date-range-picker-day';
            if (dateStr === today) classes += ' today';
            if (startDate && dateStr === startDate) classes += ' range-start';
            if (endDate && dateStr === endDate) classes += ' range-end';
            if (startDate && endDate && dateStr > startDate && dateStr < endDate) classes += ' in-range';
            if (startDate && !endDate && dateStr === startDate) classes += ' selected';
            const disabled = (minDate && dateStr < minDate) || (maxDate && dateStr > maxDate);
            if (disabled) classes += ' disabled';
            html += `<button class="${classes}" data-date="${dateStr}"${disabled ? ' disabled' : ''}>${day}</button>`;
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

        if (!startDate || (startDate && endDate)) {
            picker.dataset.dateStart = date;
            picker.dataset.dateEnd = '';
        } else {
            if (date < startDate) {
                picker.dataset.dateEnd = startDate;
                picker.dataset.dateStart = date;
            } else {
                picker.dataset.dateEnd = date;
            }
            this.updateInput(picker);

            if (!this.isShowTime(picker)) {
                this.hide(picker);
            }

            picker.dispatchEvent(new CustomEvent('ux:change', {
                detail: { value: picker.dataset.dateStart + '~' + picker.dataset.dateEnd },
                bubbles: true
            }));
        }

        this.renderCalendars(picker);
    },

    updateInput(picker) {
        const input = picker.querySelector('.ux-date-range-picker-input');
        if (!input) return;
        const start = picker.dataset.dateStart;
        const end = picker.dataset.dateEnd;
        const separator = picker.dataset.dateSeparator || '~';
        if (start && end) {
            input.value = `${start} ${separator} ${end}`;
        } else if (start) {
            input.value = start;
        } else {
            input.value = '';
        }
    },

    clear(picker) {
        picker.dataset.dateStart = '';
        picker.dataset.dateEnd = '';
        const input = picker.querySelector('.ux-date-range-picker-input');
        if (input) input.value = '';
        this.renderCalendars(picker);

        picker.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: '' },
            bubbles: true
        }));
    },

    selectPreset(picker, preset) {
        const now = new Date();
        const end = this.formatDate(now);
        let start;

        switch (preset) {
            case 'week':
                start = this.formatDate(new Date(now.getTime() - 7 * 86400000));
                break;
            case 'month':
                start = this.formatDate(new Date(now.getFullYear(), now.getMonth() - 1, now.getDate()));
                break;
            case 'quarter':
                start = this.formatDate(new Date(now.getFullYear(), now.getMonth() - 3, now.getDate()));
                break;
            default:
                return;
        }

        picker.dataset.dateStart = start;
        picker.dataset.dateEnd = end;
        this.updateInput(picker);
        this.renderCalendars(picker);

        picker.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: start + '~' + end },
            bubbles: true
        }));
    },

    handleNav(picker, action, calIdx) {
        const side = calIdx === 0 ? 'left' : 'right';
        const view = this.getViewMonth(picker, side);
        let year = view.year;
        let month = view.month;

        switch (action) {
            case 'prev-year': year--; break;
            case 'prev-month':
                month--;
                if (month < 1) { month = 12; year--; }
                break;
            case 'next-month':
                month++;
                if (month > 12) { month = 1; year++; }
                break;
            case 'next-year': year++; break;
        }

        const prefix = calIdx === 0 ? 'uxLeft' : 'uxRight';
        picker.dataset[prefix + 'ViewYear'] = String(year);
        picker.dataset[prefix + 'ViewMonth'] = String(month);
        this.renderOneCalendar(picker, calIdx, year, month);
    },

    setValue(picker, value) {
        if (!value || typeof value !== 'string') return;
        const parts = value.split('~');
        if (parts.length === 2) {
            picker.dataset.dateStart = parts[0].trim();
            picker.dataset.dateEnd = parts[1].trim();
            this.updateInput(picker);
        }
    },

    populateTimeSelects(picker) {
        const hourSelect = picker.querySelector('.ux-date-range-picker-time-hour');
        const minuteSelect = picker.querySelector('.ux-date-range-picker-time-minute');
        const secondSelect = picker.querySelector('.ux-date-range-picker-time-second');

        if (hourSelect && hourSelect.options.length === 0) {
            for (let h = 0; h < 24; h++) {
                const opt = document.createElement('option');
                opt.value = String(h);
                opt.textContent = String(h).padStart(2, '0');
                hourSelect.appendChild(opt);
            }
        }
        if (minuteSelect && minuteSelect.options.length === 0) {
            for (let m = 0; m < 60; m++) {
                const opt = document.createElement('option');
                opt.value = String(m);
                opt.textContent = String(m).padStart(2, '0');
                minuteSelect.appendChild(opt);
            }
        }
        if (secondSelect && secondSelect.options.length === 0) {
            for (let s = 0; s < 60; s++) {
                const opt = document.createElement('option');
                opt.value = String(s);
                opt.textContent = String(s).padStart(2, '0');
                secondSelect.appendChild(opt);
            }
        }

        const now = new Date();
        if (hourSelect) hourSelect.value = String(now.getHours());
        if (minuteSelect) minuteSelect.value = String(now.getMinutes());
        if (secondSelect) secondSelect.value = String(now.getSeconds());
    },

    confirm(picker) {
        const startDate = picker.dataset.dateStart;
        const endDate = picker.dataset.dateEnd;
        if (!startDate || !endDate) return;

        if (this.isShowTime(picker)) {
            const hourSelect = picker.querySelector('.ux-date-range-picker-time-hour');
            const minuteSelect = picker.querySelector('.ux-date-range-picker-time-minute');
            const secondSelect = picker.querySelector('.ux-date-range-picker-time-second');

            const hour = hourSelect ? String(hourSelect.value).padStart(2, '0') : '00';
            const minute = minuteSelect ? String(minuteSelect.value).padStart(2, '0') : '00';
            const second = secondSelect ? String(secondSelect.value).padStart(2, '0') : '00';
            const timeStr = ` ${hour}:${minute}:${second}`;

            const startParts = startDate.split(' ');
            const endParts = endDate.split(' ');
            const fullStart = startParts[0] + timeStr;
            const fullEnd = endParts[0] + timeStr;

            picker.dataset.dateStart = fullStart;
            picker.dataset.dateEnd = fullEnd;
            this.updateInput(picker);

            picker.dispatchEvent(new CustomEvent('ux:change', {
                detail: { value: fullStart + '~' + fullEnd },
                bubbles: true
            }));
        }

        this.hide(picker);
    }
};

export default DateRangePicker;
