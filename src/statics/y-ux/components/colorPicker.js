// ColorPicker 颜色选择器组件
const ColorPicker = {
    activePicker: null,

    init() {
        // 点击预览区域打开选择器
        document.addEventListener('click', (e) => {
            const preview = e.target.closest('.ux-color-picker-preview');
            if (preview) {
                const picker = preview.closest('.ux-color-picker');
                if (picker && !picker.classList.contains('ux-color-picker-disabled')) {
                    this.toggle(picker);
                }
            }

            // 点击清除按钮
            const clear = e.target.closest('.ux-color-picker-clear');
            if (clear) {
                const picker = clear.closest('.ux-color-picker');
                if (picker) {
                    e.stopPropagation();
                    this.clear(picker);
                }
            }

            // 点击预设颜色
            const preset = e.target.closest('.ux-color-picker-preset');
            if (preset) {
                const picker = preset.closest('.ux-color-picker');
                const color = preset.dataset.color;
                if (picker && color) {
                    this.setValue(picker, color);
                    this.hide(picker);
                }
            }

            // 点击外部关闭
            if (!e.target.closest('.ux-color-picker')) {
                this.hideAll();
            }
        });

        // 原生颜色 input 变化
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('ux-color-picker-native')) {
                const picker = e.target.closest('.ux-color-picker');
                if (picker) {
                    this.setValue(picker, e.target.value);
                }
            }
        });

        // 自定义颜色输入
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('ux-color-picker-custom-input')) {
                const picker = e.target.closest('.ux-color-picker');
                if (picker) {
                    let color = e.target.value;
                    if (!color.startsWith('#')) {
                        color = '#' + color;
                    }
                    if (this.isValidColor(color)) {
                        this.setValue(picker, color);
                    }
                }
            }
        });
    },

    toggle(picker) {
        if (this.activePicker === picker) {
            this.hide(picker);
        } else {
            this.show(picker);
        }
    },

    show(picker) {
        this.hideAll();

        let dropdown = picker.querySelector('.ux-color-picker-dropdown');
        if (!dropdown) {
            dropdown = this.createDropdown(picker);
        }

        dropdown.classList.add('show');
        this.activePicker = picker;
    },

    hide(picker) {
        const dropdown = picker.querySelector('.ux-color-picker-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
        if (this.activePicker === picker) {
            this.activePicker = null;
        }
    },

    hideAll() {
        document.querySelectorAll('.ux-color-picker-dropdown.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
        this.activePicker = null;
    },

    createDropdown(picker) {
        const presets = picker.dataset.colorPresets;
        const currentValue = picker.dataset.colorValue || '#3b82f6';

        const dropdown = document.createElement('div');
        dropdown.className = 'ux-color-picker-dropdown';

        // 预设颜色
        if (presets) {
            const presetsContainer = document.createElement('div');
            presetsContainer.className = 'ux-color-picker-presets';

            const colors = JSON.parse(presets);
            colors.forEach(color => {
                const preset = document.createElement('div');
                preset.className = 'ux-color-picker-preset';
                preset.style.backgroundColor = color;
                preset.dataset.color = color;
                presetsContainer.appendChild(preset);
            });

            dropdown.appendChild(presetsContainer);
        }

        // 自定义颜色
        const customContainer = document.createElement('div');
        customContainer.className = 'ux-color-picker-custom';

        const nativeInput = document.createElement('input');
        nativeInput.type = 'color';
        nativeInput.value = currentValue;
        nativeInput.className = 'ux-color-picker-native';

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.value = currentValue;
        textInput.className = 'ux-color-picker-custom-input';
        textInput.placeholder = '#000000';

        customContainer.appendChild(nativeInput);
        customContainer.appendChild(textInput);
        dropdown.appendChild(customContainer);

        picker.appendChild(dropdown);
        return dropdown;
    },

    setValue(picker, color) {
        picker.dataset.colorValue = color;

        // 更新预览
        const block = picker.querySelector('.ux-color-picker-block');
        if (block) {
            block.style.backgroundColor = color;
        }

        const text = picker.querySelector('.ux-color-picker-text');
        if (text) {
            text.textContent = color.toUpperCase();
        }

        // 更新原生 input
        const input = picker.querySelector('.ux-color-picker-input');
        if (input) {
            input.value = color;
        }

        // 更新自定义输入
        const customInput = picker.querySelector('.ux-color-picker-custom-input');
        if (customInput) {
            customInput.value = color.toUpperCase();
        }

        const nativeInput = picker.querySelector('.ux-color-picker-native');
        if (nativeInput) {
            nativeInput.value = color;
        }

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        picker.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: color },
            bubbles: true
        }));
    },

    clear(picker) {
        this.setValue(picker, '');

        // 更新清除按钮显示
        const clear = picker.querySelector('.ux-color-picker-clear');
        if (clear) {
            clear.style.display = 'none';
        }
    },

    isValidColor(color) {
        return /^#[0-9A-Fa-f]{6}$/.test(color);
    },

    // 程序化设置值
    setColor(id, color) {
        const picker = document.querySelector(`#${id}.ux-color-picker`);
        if (picker) {
            this.setValue(picker, color);
        }
    },

    // 获取值
    getColor(id) {
        const picker = document.querySelector(`#${id}.ux-color-picker`);
        return picker ? picker.dataset.colorValue : null;
    }
};

export default ColorPicker;
