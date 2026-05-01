// DatePicker 日期选择器组件
const DatePicker = {
    init() {
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const picker = e.target.closest('.ux-date-picker');
            if (!picker) {
                this.hideAll();
                return;
            }

            const clear = e.target.closest('.ux-date-picker-clear');
            if (clear) {
                e.stopPropagation();
                this.clear(picker);
                return;
            }

            const day = e.target.closest('.ux-date-picker-day');
            if (day) {
                e.stopPropagation();
                const date = day.dataset.date;
                if (date) {
                    this.selectDate(picker, date);
                }
                return;
            }

            const navBtn = e.target.closest('.ux-date-picker-nav-btn');
            if (navBtn) {
                e.stopPropagation();
                const action = navBtn.dataset.uxAction;
                if (action) {
                    this.handleNav(picker, action);
                }
                return;
            }

            const todayBtn = e.target.closest('.ux-date-picker-today-btn');
            if (todayBtn) {
                e.stopPropagation();
                this.selectToday(picker);
                return;
            }

            const confirmBtn = e.target.closest('.ux-date-picker-confirm-btn');
            if (confirmBtn) {
                e.stopPropagation();
                this.confirm(picker);
                return;
            }

            const inputWrapper = e.target.closest('.ux-date-picker-input-wrapper');
            if (inputWrapper) {
                this.toggle(picker);
                return;
            }
        });

        document.addEventListener('change', (e) => {
            if (!e.target || !e.target.classList) return;

            if (e.target.classList.contains('ux-date-picker-time-hour') ||
                e.target.classList.contains('ux-date-picker-time-minute') ||
                e.target.classList.contains('ux-date-picker-time-second')) {
                const picker = e.target.closest('.ux-date-picker');
                if (picker) {
                    this.updateTimeFromSelects(picker);
                }
            }
        });
    },

    parseDate(str) {
        if (!str || typeof str !== 'string') return null;
        const dateTime = str.split(' ');
        const datePart = dateTime[0];
        const timePart = dateTime[1] || null;
        const parts = datePart.split('-');
        if (parts.length !== 3) return null;
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const d = parseInt(parts[2], 10);
        if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
        const result = { year: y, month: m, day: d };
        if (timePart) {
            const tp = timePart.split(':');
            result.hour = parseInt(tp[0], 10) || 0;
            result.minute = parseInt(tp[1], 10) || 0;
            result.second = parseInt(tp[2], 10) || 0;
        }
        return result;
    },

    getViewMonth(picker) {
        const vy = parseInt(picker.dataset.uxViewYear, 10);
        const vm = parseInt(picker.dataset.uxViewMonth, 10);
        if (!isNaN(vy) && !isNaN(vm) && vy > 0 && vm >= 1 && vm <= 12) {
            return { year: vy, month: vm };
        }
        const val = picker.dataset.dateValue;
        const parsed = this.parseDate(val);
        if (parsed) return { year: parsed.year, month: parsed.month };
        const now = new Date();
        return { year: now.getFullYear(), month: now.getMonth() + 1 };
    },

    isShowTime(picker) {
        return picker.dataset.showTime === 'true';
    },

    getTimeValues(picker) {
        return {
            hour: parseInt(picker.dataset.timeHour, 10) || 0,
            minute: parseInt(picker.dataset.timeMinute, 10) || 0,
            second: parseInt(picker.dataset.timeSecond, 10) || 0
        };
    },

    setTimeValues(picker, hour, minute, second) {
        picker.dataset.timeHour = String(hour);
        picker.dataset.timeMinute = String(minute);
        picker.dataset.timeSecond = String(second);
    },

    toggle(picker) {
        if (picker.classList.contains('ux-date-picker-open')) {
            this.hide(picker);
        } else {
            this.show(picker);
        }
    },

    show(picker) {
        this.hideAll();
        picker.classList.add('ux-date-picker-open');
        const dropdown = picker.querySelector('.ux-date-picker-dropdown');
        if (dropdown) {
            dropdown.classList.add('show');
        }
        const view = this.getViewMonth(picker);
        picker.dataset.uxViewYear = String(view.year);
        picker.dataset.uxViewMonth = String(view.month);
        this.renderMonth(picker, view.year, view.month);

        if (this.isShowTime(picker)) {
            this.populateTimeSelects(picker);
        }
    },

    hide(picker) {
        picker.classList.remove('ux-date-picker-open');
        const dropdown = picker.querySelector('.ux-date-picker-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    },

    hideAll() {
        document.querySelectorAll('.ux-date-picker-open').forEach(picker => {
            this.hide(picker);
        });
    },

    selectDate(picker, date) {
        const minDate = picker.dataset.dateMin;
        const maxDate = picker.dataset.dateMax;
        if (minDate && date < minDate) return;
        if (maxDate && date > maxDate) return;

        picker.dataset.dateValue = date;

        picker.querySelectorAll('.ux-date-picker-day').forEach(day => {
            day.classList.toggle('selected', day.dataset.date === date);
        });

        if (this.isShowTime(picker)) {
            const time = this.getTimeValues(picker);
            const fullValue = `${date} ${String(time.hour).padStart(2, '0')}:${String(time.minute).padStart(2, '0')}:${String(time.second).padStart(2, '0')}`;
            const input = picker.querySelector('.ux-date-picker-input');
            if (input) input.value = fullValue;
        } else {
            const input = picker.querySelector('.ux-date-picker-input');
            if (input) input.value = date;
            this.hide(picker);

            picker.dispatchEvent(new CustomEvent('ux:change', {
                detail: { value: date },
                bubbles: true
            }));
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
            const fullValue = `${dateValue} ${String(time.hour).padStart(2, '0')}:${String(time.minute).padStart(2, '0')}:${String(time.second).padStart(2, '0')}`;
            const input = picker.querySelector('.ux-date-picker-input');
            if (input) input.value = fullValue;

            picker.dispatchEvent(new CustomEvent('ux:change', {
                detail: { value: fullValue },
                bubbles: true
            }));
        } else {
            picker.dispatchEvent(new CustomEvent('ux:change', {
                detail: { value: dateValue },
                bubbles: true
            }));
        }

        this.hide(picker);
    },

    clear(picker) {
        const input = picker.querySelector('.ux-date-picker-input');
        if (input) input.value = '';
        picker.dataset.dateValue = '';
        picker.querySelectorAll('.ux-date-picker-day').forEach(day => {
            day.classList.remove('selected');
        });

        picker.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: '' },
            bubbles: true
        }));
    },

    handleNav(picker, action) {
        const view = this.getViewMonth(picker);
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
        const selectedDate = picker.dataset.dateValue ? picker.dataset.dateValue.split(' ')[0] : '';
        const minDate = picker.dataset.dateMin || null;
        const maxDate = picker.dataset.dateMax || null;

        const title = picker.querySelector('.ux-date-picker-title');
        if (title) {
            title.textContent = `${year}年${month}月`;
        }

        picker.dataset.uxViewYear = String(year);
        picker.dataset.uxViewMonth = String(month);

        const daysGrid = picker.querySelector('.ux-date-picker-days');
        if (!daysGrid) return;

        const firstDay = new Date(year, month - 1, 1);
        const daysInMonth = new Date(year, month, 0).getDate();
        const startWeekday = firstDay.getDay();
        const prevMonthDays = new Date(year, month - 1, 0).getDate();

        let html = '';

        for (let i = startWeekday - 1; i >= 0; i--) {
            const day = prevMonthDays - i;
            const pm = month - 1;
            const py = pm < 1 ? year - 1 : year;
            const rm = pm < 1 ? 12 : pm;
            const dateStr = `${py}-${String(rm).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const disabled = this.isOutOfRange(dateStr, minDate, maxDate);
            html += `<button class="ux-date-picker-day other-month${disabled ? ' disabled' : ''}" data-date="${dateStr}"${disabled ? ' disabled' : ''}>${day}</button>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            let classes = 'ux-date-picker-day';
            if (dateStr === today) classes += ' today';
            if (dateStr === selectedDate) classes += ' selected';
            const disabled = this.isOutOfRange(dateStr, minDate, maxDate);
            if (disabled) classes += ' disabled';
            html += `<button class="${classes}" data-date="${dateStr}"${disabled ? ' disabled' : ''}>${day}</button>`;
        }

        const remainingDays = 42 - (startWeekday + daysInMonth);
        for (let day = 1; day <= remainingDays; day++) {
            const nm = month + 1;
            const ny = nm > 12 ? year + 1 : year;
            const rn = nm > 12 ? 1 : nm;
            const dateStr = `${ny}-${String(rn).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const disabled = this.isOutOfRange(dateStr, minDate, maxDate);
            html += `<button class="ux-date-picker-day other-month${disabled ? ' disabled' : ''}" data-date="${dateStr}"${disabled ? ' disabled' : ''}>${day}</button>`;
        }

        daysGrid.innerHTML = html;
    },

    populateTimeSelects(picker) {
        const time = this.getTimeValues(picker);

        const hourSelect = picker.querySelector('.ux-date-picker-time-hour');
        const minuteSelect = picker.querySelector('.ux-date-picker-time-minute');
        const secondSelect = picker.querySelector('.ux-date-picker-time-second');

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

        if (hourSelect) hourSelect.value = String(time.hour);
        if (minuteSelect) minuteSelect.value = String(time.minute);
        if (secondSelect) secondSelect.value = String(time.second);
    },

    updateTimeFromSelects(picker) {
        const hourSelect = picker.querySelector('.ux-date-picker-time-hour');
        const minuteSelect = picker.querySelector('.ux-date-picker-time-minute');
        const secondSelect = picker.querySelector('.ux-date-picker-time-second');

        const hour = hourSelect ? parseInt(hourSelect.value, 10) : 0;
        const minute = minuteSelect ? parseInt(minuteSelect.value, 10) : 0;
        const second = secondSelect ? parseInt(secondSelect.value, 10) : 0;

        this.setTimeValues(picker, hour, minute, second);

        // 更新输入框预览
        const dateValue = picker.dataset.dateValue;
        if (dateValue) {
            const datePart = dateValue.split(' ')[0];
            const fullValue = `${datePart} ${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}:${String(second).padStart(2, '0')}`;
            const input = picker.querySelector('.ux-date-picker-input');
            if (input) input.value = fullValue;
        }
    },

    isOutOfRange(dateStr, minDate, maxDate) {
        if (minDate && dateStr < minDate) return true;
        if (maxDate && dateStr > maxDate) return true;
        return false;
    },

    formatDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    },

    setValue(picker, date) {
        if (!date) return;
        picker.dataset.dateValue = date;
        const input = picker.querySelector('.ux-date-picker-input');
        if (input) input.value = date;

        const parsed = this.parseDate(date);
        if (parsed && parsed.hour !== undefined) {
            this.setTimeValues(picker, parsed.hour, parsed.minute, parsed.second);
        }
    }
};

export default DatePicker;
