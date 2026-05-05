// Slider 滑块组件
const Slider = {
    activeSlider: null,
    activeHandle: null,
    startX: 0,
    startY: 0,
    startValue: 0,

    init() {
        // 鼠标/触摸事件
        document.addEventListener('mousedown', (e) => this.handleStart(e));
        document.addEventListener('touchstart', (e) => this.handleStart(e), { passive: false });

        document.addEventListener('mousemove', (e) => this.handleMove(e));
        document.addEventListener('touchmove', (e) => this.handleMove(e), { passive: false });

        document.addEventListener('mouseup', () => this.handleEnd());
        document.addEventListener('touchend', () => this.handleEnd());

        // 点击轨道跳转
        document.addEventListener('click', (e) => {
            const track = e.target.closest('.ux-slider-track');
            if (track && !e.target.closest('.ux-slider-handle')) {
                const slider = track.closest('.ux-slider');
                if (slider && !slider.classList.contains('ux-slider-disabled')) {
                    this.jumpToPosition(slider, e);
                }
            }
        });
    },

    handleStart(e) {
        const handle = e.target.closest('.ux-slider-handle');
        if (!handle) return;

        const slider = handle.closest('.ux-slider');
        if (!slider || slider.classList.contains('ux-slider-disabled')) return;

        e.preventDefault();

        this.activeSlider = slider;
        this.activeHandle = handle;
        handle.classList.add('dragging');

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        this.startX = clientX;
        this.startY = clientY;

        const isVertical = slider.classList.contains('ux-slider-vertical');
        const value = this.getHandleValue(slider, handle);
        this.startValue = value;
    },

    handleMove(e) {
        if (!this.activeSlider || !this.activeHandle) return;

        e.preventDefault();

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const isVertical = this.activeSlider.classList.contains('ux-slider-vertical');
        const track = this.activeSlider.querySelector('.ux-slider-track');
        const rect = track.getBoundingClientRect();

        let percent;
        if (isVertical) {
            percent = (rect.bottom - clientY) / rect.height;
        } else {
            percent = (clientX - rect.left) / rect.width;
        }

        percent = Math.max(0, Math.min(1, percent));

        const min = parseFloat(this.activeSlider.dataset.sliderMin) || 0;
        const max = parseFloat(this.activeSlider.dataset.sliderMax) || 100;
        const step = parseFloat(this.activeSlider.dataset.sliderStep) || 1;

        let value = min + percent * (max - min);
        value = Math.round(value / step) * step;
        value = Math.max(min, Math.min(max, value));

        this.setValue(this.activeSlider, this.activeHandle, value);
    },

    handleEnd() {
        if (!this.activeSlider || !this.activeHandle) return;

        this.activeHandle.classList.remove('dragging');

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        const value = this.getValue(this.activeSlider);
        this.activeSlider.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value },
            bubbles: true
        }));

        this.activeSlider = null;
        this.activeHandle = null;
    },

    jumpToPosition(slider, e) {
        const track = slider.querySelector('.ux-slider-track');
        const rect = track.getBoundingClientRect();
        const isVertical = slider.classList.contains('ux-slider-vertical');
        const isRange = slider.classList.contains('ux-slider-range');

        let percent;
        if (isVertical) {
            percent = (rect.bottom - e.clientY) / rect.height;
        } else {
            percent = (e.clientX - rect.left) / rect.width;
        }

        percent = Math.max(0, Math.min(1, percent));

        const min = parseFloat(slider.dataset.sliderMin) || 0;
        const max = parseFloat(slider.dataset.sliderMax) || 100;
        const step = parseFloat(slider.dataset.sliderStep) || 1;

        let value = min + percent * (max - min);
        value = Math.round(value / step) * step;
        value = Math.max(min, Math.min(max, value));

        if (isRange) {
            // 范围滑块：找到最近的手柄
            const handles = slider.querySelectorAll('.ux-slider-handle');
            let closestHandle = null;
            let closestDistance = Infinity;

            handles.forEach(handle => {
                const handleValue = this.getHandleValue(slider, handle);
                const distance = Math.abs(handleValue - value);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestHandle = handle;
                }
            });

            if (closestHandle) {
                this.setValue(slider, closestHandle, value);
            }
        } else {
            const handle = slider.querySelector('.ux-slider-handle');
            if (handle) {
                this.setValue(slider, handle, value);
            }
        }
    },

    setValue(slider, handle, value) {
        const min = parseFloat(slider.dataset.sliderMin) || 0;
        const max = parseFloat(slider.dataset.sliderMax) || 100;
        const format = slider.dataset.sliderFormat;

        const percent = ((value - min) / (max - min)) * 100;
        const isVertical = slider.classList.contains('ux-slider-vertical');

        if (isVertical) {
            handle.style.bottom = `${percent}%`;
        } else {
            handle.style.left = `${percent}%`;
        }

        // 更新 tooltip
        const tooltip = handle.querySelector('.ux-slider-tooltip');
        if (tooltip) {
            tooltip.textContent = format ? this.formatValue(format, value) : value;
        }

        // 更新进度条
        this.updateProgress(slider);

        // 更新 dataset
        if (slider.classList.contains('ux-slider-range')) {
            const handles = Array.from(slider.querySelectorAll('.ux-slider-handle'));
            const values = handles.map(h => this.getHandleValue(slider, h));
            slider.dataset.sliderValue = JSON.stringify(values);
        } else {
            slider.dataset.sliderValue = value;
        }
    },

    updateProgress(slider) {
        const progress = slider.querySelector('.ux-slider-progress');
        if (!progress) return;

        const isRange = slider.classList.contains('ux-slider-range');
        const isVertical = slider.classList.contains('ux-slider-vertical');

        if (isRange) {
            const handles = Array.from(slider.querySelectorAll('.ux-slider-handle'));
            const values = handles.map(h => this.getHandleValue(slider, h));
            const minVal = Math.min(...values);
            const maxVal = Math.max(...values);

            const min = parseFloat(slider.dataset.sliderMin) || 0;
            const max = parseFloat(slider.dataset.sliderMax) || 100;

            const startPercent = ((minVal - min) / (max - min)) * 100;
            const endPercent = ((maxVal - min) / (max - min)) * 100;

            if (isVertical) {
                progress.style.bottom = `${startPercent}%`;
                progress.style.height = `${endPercent - startPercent}%`;
            } else {
                progress.style.left = `${startPercent}%`;
                progress.style.width = `${endPercent - startPercent}%`;
            }
        } else {
            const handle = slider.querySelector('.ux-slider-handle');
            if (handle) {
                const value = this.getHandleValue(slider, handle);
                const min = parseFloat(slider.dataset.sliderMin) || 0;
                const max = parseFloat(slider.dataset.sliderMax) || 100;
                const percent = ((value - min) / (max - min)) * 100;

                if (isVertical) {
                    progress.style.height = `${percent}%`;
                } else {
                    progress.style.width = `${percent}%`;
                }
            }
        }
    },

    getHandleValue(slider, handle) {
        const min = parseFloat(slider.dataset.sliderMin) || 0;
        const max = parseFloat(slider.dataset.sliderMax) || 100;
        const isVertical = slider.classList.contains('ux-slider-vertical');

        const style = handle.style;
        const percentStr = isVertical ? style.bottom : style.left;
        const percent = parseFloat(percentStr) || 0;

        return min + (percent / 100) * (max - min);
    },

    getValue(slider) {
        if (slider.classList.contains('ux-slider-range')) {
            const handles = Array.from(slider.querySelectorAll('.ux-slider-handle'));
            return handles.map(h => this.getHandleValue(slider, h));
        }
        return parseFloat(slider.dataset.sliderValue) || 0;
    },

    formatValue(format, value) {
        return format.replace('%s', value);
    },

    // 程序化设置值
    setSliderValue(id, value) {
        const slider = document.querySelector(`#${id}.ux-slider`);
        if (!slider) return;

        if (Array.isArray(value) && slider.classList.contains('ux-slider-range')) {
            const handles = slider.querySelectorAll('.ux-slider-handle');
            value.forEach((val, i) => {
                if (handles[i]) {
                    this.setValue(slider, handles[i], val);
                }
            });
        } else {
            const handle = slider.querySelector('.ux-slider-handle');
            if (handle) {
                this.setValue(slider, handle, value);
            }
        }
    }
};

export default Slider;
