// Calendar 日历组件
const Calendar = {
    init() {
        document.querySelectorAll('.ux-calendar').forEach(calendar => {
            this.render(calendar);
        });

        window.addEventListener('y:updated', (e) => {
            document.querySelectorAll('.ux-calendar').forEach(calendar => {
                if (!calendar.querySelector('.ux-calendar-day')) {
                    this.render(calendar);
                }
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const calendar = e.target.closest('.ux-calendar');
            if (!calendar) return;

            const prevBtn = e.target.closest('[data-ux-action="prev"]');
            if (prevBtn) {
                e.stopPropagation();
                this.navigate(calendar, -1);
                return;
            }

            const nextBtn = e.target.closest('[data-ux-action="next"]');
            if (nextBtn) {
                e.stopPropagation();
                this.navigate(calendar, 1);
                return;
            }

            const modeBtn = e.target.closest('.ux-calendar-mode-btn');
            if (modeBtn) {
                e.stopPropagation();
                const mode = modeBtn.dataset.mode;
                if (mode) {
                    this.switchMode(calendar, mode);
                }
                return;
            }

            const dayCell = e.target.closest('.ux-calendar-day:not(.disabled)');
            if (dayCell) {
                e.stopPropagation();
                this.selectDate(calendar, dayCell);
                return;
            }

            const monthCell = e.target.closest('.ux-calendar-month:not(.disabled)');
            if (monthCell) {
                e.stopPropagation();
                this.selectMonth(calendar, monthCell);
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

    getViewMonth(calendar) {
        const vy = parseInt(calendar.dataset.uxViewYear, 10);
        const vm = parseInt(calendar.dataset.uxViewMonth, 10);
        if (!isNaN(vy) && !isNaN(vm) && vy > 0 && vm >= 1 && vm <= 12) {
            return { year: vy, month: vm };
        }
        const val = calendar.dataset.calendarValue;
        const parsed = this.parseDate(val);
        if (parsed) return { year: parsed.year, month: parsed.month };
        const now = new Date();
        return { year: now.getFullYear(), month: now.getMonth() + 1 };
    },

    render(calendar) {
        const mode = calendar.dataset.calendarMode || 'month';
        const view = this.getViewMonth(calendar);
        calendar.dataset.uxViewYear = String(view.year);
        calendar.dataset.uxViewMonth = String(view.month);

        const title = calendar.querySelector('.ux-calendar-title');
        if (title) {
            if (mode === 'month') {
                title.textContent = `${view.year}年${view.month}月`;
            } else {
                title.textContent = `${view.year}年`;
            }
        }

        if (mode === 'month') {
            this.renderMonthView(calendar, view.year, view.month);
        } else {
            this.renderYearView(calendar, view.year);
        }
    },

    renderMonthView(calendar, year, month) {
        if (isNaN(year) || isNaN(month) || year < 1 || month < 1 || month > 12) {
            const now = new Date();
            year = now.getFullYear();
            month = now.getMonth() + 1;
        }

        const daysContainer = calendar.querySelector('.ux-calendar-days');
        if (!daysContainer) return;

        daysContainer.className = 'ux-calendar-days';

        const weekdaysEl = calendar.querySelector('.ux-calendar-weekdays');
        if (weekdaysEl) weekdaysEl.style.display = '';

        const today = this.formatDate(new Date());
        const selectedDate = calendar.dataset.calendarValue;
        const validRange = this.getValidRange(calendar);

        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = new Date(year, month, 0).getDate();
        const startDayOfWeek = firstDay.getDay();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        let html = '';

        for (let i = startDayOfWeek - 1; i >= 0; i--) {
            const day = prevMonthLastDay - i;
            html += `<div class="ux-calendar-day other-month">${day}</div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const currentDateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = currentDateStr === today;
            const isSelected = selectedDate === currentDateStr;
            const isDisabled = this.isOutOfRange(currentDateStr, validRange);

            let classes = 'ux-calendar-day';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            if (isDisabled) classes += ' disabled';

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
        if (isNaN(year) || year < 1) {
            year = new Date().getFullYear();
        }

        const bodyEl = calendar.querySelector('.ux-calendar-body');
        if (!bodyEl) return;

        const selectedDate = calendar.dataset.calendarValue;
        const parsed = this.parseDate(selectedDate);
        const selectedYear = parsed ? parsed.year : null;
        const selectedMonth = parsed ? parsed.month : null;

        const weekdaysEl = bodyEl.querySelector('.ux-calendar-weekdays');
        if (weekdaysEl) weekdaysEl.style.display = 'none';

        let daysContainer = bodyEl.querySelector('.ux-calendar-days');
        if (!daysContainer) {
            daysContainer = document.createElement('div');
            daysContainer.className = 'ux-calendar-days ux-calendar-months';
            bodyEl.appendChild(daysContainer);
        } else {
            daysContainer.className = 'ux-calendar-days ux-calendar-months';
        }

        const months = ['1月', '2月', '3月', '4月', '5月', '6月',
                       '7月', '8月', '9月', '10月', '11月', '12月'];

        let html = '';
        months.forEach((monthName, index) => {
            const isSelected = year === selectedYear && (index + 1) === selectedMonth;
            let classes = 'ux-calendar-month';
            if (isSelected) classes += ' selected';
            html += `<div class="${classes}" data-month="${index}">${monthName}</div>`;
        });

        daysContainer.innerHTML = html;
    },

    getValidRange(calendar) {
        const raw = calendar.dataset.calendarValidRange;
        if (!raw) return null;
        try {
            return JSON.parse(raw);
        } catch {
            return null;
        }
    },

    isOutOfRange(dateStr, validRange) {
        if (!validRange) return false;
        if (validRange.start && dateStr < validRange.start) return true;
        if (validRange.end && dateStr > validRange.end) return true;
        return false;
    },

    navigate(calendar, direction) {
        const mode = calendar.dataset.calendarMode || 'month';
        const view = this.getViewMonth(calendar);
        let year = view.year;
        let month = view.month;

        if (mode === 'month') {
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

        calendar.querySelectorAll('.ux-calendar-mode-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.mode === mode);
        });

        const weekdaysEl = calendar.querySelector('.ux-calendar-weekdays');
        if (weekdaysEl) {
            weekdaysEl.style.display = mode === 'month' ? '' : 'none';
        }

        this.render(calendar);
    },

    selectDate(calendar, dayCell) {
        const date = dayCell.dataset.date;
        if (!date) return;

        const validRange = this.getValidRange(calendar);
        if (this.isOutOfRange(date, validRange)) return;

        calendar.dataset.calendarValue = date;

        calendar.querySelectorAll('.ux-calendar-day').forEach(cell => {
            cell.classList.remove('selected');
        });
        dayCell.classList.add('selected');

        this.triggerChange(calendar, date);
    },

    selectMonth(calendar, monthCell) {
        const month = parseInt(monthCell.dataset.month, 10);
        if (isNaN(month)) return;

        const view = this.getViewMonth(calendar);
        const year = view.year;
        calendar.dataset.uxViewYear = String(year);
        calendar.dataset.uxViewMonth = String(month + 1);

        this.switchMode(calendar, 'month');
    },

    triggerChange(calendar, date) {
        calendar.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: date },
            bubbles: true
        }));
    },

    formatDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
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

export default Calendar;
