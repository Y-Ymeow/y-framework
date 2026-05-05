// UX-Live Bridge - UX 组件与 LiveComponent 的通用桥接层
// 核心原理：UX 组件派发 ux:change 事件 → 桥接层同步到 Live 的 data-live-model → 自动触发 __updateProperty

const UXLiveBridge = {
    init() {
        // 监听所有 UX 组件的 ux:change 事件
        document.addEventListener('ux:change', (e) => {
            this.handleUXChange(e.target, e.detail);
        });

        // 监听 Live 更新完成事件，反向同步到 UX 组件
        window.addEventListener('y:updated', (e) => {
            this.handleLiveUpdate(e.detail);
        });

        // 初始化：为已有 data-ux-model 的组件创建隐藏 input
        document.querySelectorAll('[data-ux-model]').forEach(el => {
            this.ensureHiddenInput(el);
        });
    },

    handleUXChange(el, detail) {
        const uxModel = el.dataset.uxModel;
        if (!uxModel) return;

        const liveEl = el.closest('[data-live]');
        if (!liveEl) return;

        // 确保 hidden input 存在
        const hiddenInput = this.ensureHiddenInput(el);
        if (!hiddenInput) return;

        // 更新 hidden input 的值
        const value = detail.value !== undefined ? detail.value : '';
        hiddenInput.value = typeof value === 'object' ? JSON.stringify(value) : String(value);

        // 触发 change 事件 → Live 的 data-live-model 监听器会捕获
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

        // 同时也触发 input 事件（兼容 text 类型的 debounce 逻辑）
        if (hiddenInput.dataset.liveEvent === 'input') {
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    handleLiveUpdate(detail) {
        if (!detail || !detail.el || !detail.data) return;

        const { el: liveEl, data } = detail;
        if (!data.patches) return;

        // 查找此 Live 组件内所有带 data-ux-model 的 UX 组件
        const uxComponents = liveEl.querySelectorAll('[data-ux-model]');
        uxComponents.forEach(uxEl => {
            const property = uxEl.dataset.uxModel;
            if (property in data.patches) {
                const newValue = data.patches[property];
                this.syncToUXComponent(uxEl, property, newValue);
            }
        });
    },

    ensureHiddenInput(el) {
        // 查找已有的 hidden input
        let hiddenInput = el.querySelector('.ux-live-bridge-input');
        
        if (!hiddenInput) {
            const uxModel = el.dataset.uxModel;
            if (!uxModel) return null;

            // 创建隐藏 input，带 data-live-model
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = uxModel;
            hiddenInput.className = 'ux-live-bridge-input';
            hiddenInput.setAttribute('data-live-model', uxModel);
            hiddenInput.setAttribute('data-live-debounce', '0');

            // 设置初始值
            const currentValue = this.getCurrentValue(el);
            hiddenInput.value = typeof currentValue === 'object' 
                ? JSON.stringify(currentValue) 
                : String(currentValue ?? '');

            el.appendChild(hiddenInput);

            // 通知 Live 重新扫描此组件，绑定新的 data-live-model
            if (window.L && typeof window.L.boot === 'function') {
                const liveEl = el.closest('[data-live]');
                if (liveEl && liveEl._liveDispatch) {
                    // 手动绑定 live model
                    this.bindLiveModel(hiddenInput, liveEl);
                }
            }
        }

        return hiddenInput;
    },

    bindLiveModel(inputEl, liveEl) {
        if (inputEl._y_live_model_bound) return;
        inputEl._y_live_model_bound = true;

        const property = inputEl.dataset.liveModel;
        const stateRef = liveEl._y_live_state_ref;
        const state = liveEl._y_state;
        const componentClass = liveEl._y_live_component_class;

        inputEl.addEventListener('change', (e) => {
            if (liveEl._liveDispatch) {
                liveEl._liveDispatch('__updateProperty', e, {
                    property: property,
                    value: inputEl.value
                });
            }
        });
    },

    getCurrentValue(el) {
        // 尝试从组件的 data 属性中获取当前值
        const dataKeys = [
            'datePickerValue', 'calendarValue', 'tagValue',
            'transferTarget', 'treeSelectValue', 'sliderValue',
            'rateValue', 'colorPickerValue', 'selectValue'
        ];

        for (const key of dataKeys) {
            const attrName = 'data-' + key.replace(/([A-Z])/g, '-$1').toLowerCase();
            const value = el.getAttribute(attrName);
            if (value !== null) {
                try {
                    return JSON.parse(value);
                } catch {
                    return value;
                }
            }
        }

        // 尝试从 hidden input 获取
        const hiddenInput = el.querySelector('.ux-tag-input-hidden, input[type="hidden"]');
        if (hiddenInput && hiddenInput.value) {
            return hiddenInput.value;
        }

        return '';
    },

    syncToUXComponent(uxEl, property, value) {
        const componentType = this.detectComponentType(uxEl);
        if (!componentType) return;

        // 根据组件类型调用对应的 setValue 方法
        const ux = window.UX;
        if (!ux) return;

        switch (componentType) {
            case 'date-picker':
                if (ux.datePicker && typeof ux.datePicker.setValue === 'function') {
                    ux.datePicker.setValue(uxEl, value);
                }
                break;

            case 'date-range-picker':
                if (ux.dateRangePicker && typeof ux.dateRangePicker.setValue === 'function') {
                    ux.dateRangePicker.setValue(uxEl, value);
                }
                break;

            case 'calendar':
                if (ux.calendar && typeof ux.calendar.setValue === 'function') {
                    ux.calendar.setValue(uxEl, value);
                }
                break;

            case 'tag-input':
                if (ux.tagInput) {
                    this.syncTagInput(uxEl, value);
                }
                break;

            case 'transfer':
                if (ux.transfer) {
                    this.syncTransfer(uxEl, value);
                }
                break;

            case 'tree-select':
                if (ux.treeSelect && typeof ux.treeSelect.setValue === 'function') {
                    ux.treeSelect.setValue(uxEl, value);
                }
                break;

            case 'slider':
                if (ux.slider && typeof ux.slider.setSliderValue === 'function') {
                    ux.slider.setSliderValue(uxEl.id, value);
                }
                break;

            case 'rate':
                if (ux.rate && typeof ux.rate.setValue === 'function') {
                    ux.rate.setValue(uxEl.id, value);
                }
                break;

            case 'color-picker':
                if (ux.colorPicker && typeof ux.colorPicker.setColor === 'function') {
                    ux.colorPicker.setColor(uxEl.id, value);
                }
                break;

            default:
                // 通用方式：尝试触发自定义事件
                uxEl.dispatchEvent(new CustomEvent('ux:setValue', {
                    detail: { property, value },
                    bubbles: false
                }));
        }
    },

    detectComponentType(el) {
        if (el.classList.contains('ux-date-picker')) return 'date-picker';
        if (el.classList.contains('ux-date-range-picker')) return 'date-range-picker';
        if (el.classList.contains('ux-calendar')) return 'calendar';
        if (el.classList.contains('ux-tag-input')) return 'tag-input';
        if (el.classList.contains('ux-transfer')) return 'transfer';
        if (el.classList.contains('ux-tree-select')) return 'tree-select';
        if (el.classList.contains('ux-slider')) return 'slider';
        if (el.classList.contains('ux-rate')) return 'rate';
        if (el.classList.contains('ux-color-picker')) return 'color-picker';
        if (el.classList.contains('ux-select')) return 'select';
        return null;
    },

    syncTagInput(uxEl, value) {
        const values = Array.isArray(value) ? value : (typeof value === 'string' ? value.split(',').filter(Boolean) : []);
        const container = uxEl.querySelector('.ux-tag-input-container');
        if (!container) return;

        // 移除现有标签
        container.querySelectorAll('.ux-tag-input-tag').forEach(tag => tag.remove());

        // 添加新标签
        values.forEach(tagValue => {
            const tag = document.createElement('span');
            tag.className = 'ux-tag-input-tag';
            tag.innerHTML = `${tagValue}<span class="ux-tag-input-tag-remove"><i class="bi bi-x"></i></span>`;
            const input = container.querySelector('.ux-tag-input-field');
            if (input) {
                container.insertBefore(tag, input);
            } else {
                container.appendChild(tag);
            }
        });

        // 更新 hidden input
        const hidden = uxEl.querySelector('.ux-tag-input-hidden');
        if (hidden) hidden.value = values.join(',');
    },

    syncTransfer(uxEl, value) {
        const targetKeys = Array.isArray(value) ? value : [];
        const panels = uxEl.querySelectorAll('.ux-transfer-panel');
        if (panels.length < 2) return;

        const leftList = panels[0].querySelector('.ux-transfer-panel-list');
        const rightList = panels[1].querySelector('.ux-transfer-panel-list');
        if (!leftList || !rightList) return;

        // 移动项目到对应面板
        const allItems = uxEl.querySelectorAll('.ux-transfer-item');
        allItems.forEach(item => {
            const key = item.dataset.key;
            const isTarget = targetKeys.includes(key);
            const currentSide = item.dataset.side;

            if (isTarget && currentSide === 'left') {
                item.dataset.side = 'right';
                rightList.appendChild(item);
            } else if (!isTarget && currentSide === 'right') {
                item.dataset.side = 'left';
                leftList.appendChild(item);
            }
        });

        // 更新计数
        if (window.UX && window.UX.transfer) {
            window.UX.transfer.updateCount(uxEl);
        }
    }
};

export default UXLiveBridge;
