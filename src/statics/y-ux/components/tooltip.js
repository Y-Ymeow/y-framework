// Tooltip 组件
const Tooltip = {
    tooltips: new Map(),

    init() {
        // 使用事件委托处理 tooltip
        document.addEventListener('mouseenter', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-tooltip]');
            if (wrapper && wrapper.dataset.tooltipTrigger !== 'click') {
                this.show(wrapper);
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-tooltip]');
            if (wrapper && wrapper.dataset.tooltipTrigger !== 'click') {
                this.hide(wrapper);
            }
        }, true);

        document.addEventListener('focus', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-tooltip]');
            if (wrapper && wrapper.dataset.tooltipTrigger === 'focus') {
                this.show(wrapper);
            }
        }, true);

        document.addEventListener('blur', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-tooltip]');
            if (wrapper && wrapper.dataset.tooltipTrigger === 'focus') {
                this.hide(wrapper);
            }
        }, true);

        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-tooltip]');
            if (wrapper && wrapper.dataset.tooltipTrigger === 'click') {
                e.preventDefault();
                this.toggle(wrapper);
            } else {
                // 点击其他地方关闭所有 click 触发的 tooltip
                this.hideAllClickTooltips();
            }
        });
    },

    show(wrapper) {
        const content = wrapper.dataset.tooltip;
        if (!content) return;

        const delay = parseInt(wrapper.dataset.tooltipDelay) || 0;

        setTimeout(() => {
            let tooltip = this.tooltips.get(wrapper);

            if (!tooltip) {
                tooltip = this.createTooltip(wrapper);
                this.tooltips.set(wrapper, tooltip);
            }

            tooltip.classList.add('show');
        }, delay);
    },

    hide(wrapper) {
        const tooltip = this.tooltips.get(wrapper);
        if (tooltip) {
            tooltip.classList.remove('show');
        }
    },

    toggle(wrapper) {
        const tooltip = this.tooltips.get(wrapper);
        if (tooltip && tooltip.classList.contains('show')) {
            this.hide(wrapper);
        } else {
            this.show(wrapper);
        }
    },

    createTooltip(wrapper) {
        const content = wrapper.dataset.tooltip;
        const placement = wrapper.dataset.tooltipPlacement || 'top';
        const showArrow = wrapper.dataset.tooltipArrow !== 'false';
        const maxWidth = wrapper.dataset.tooltipMaxWidth;

        const tooltip = document.createElement('div');
        tooltip.className = 'ux-tooltip';
        tooltip.textContent = content;
        tooltip.dataset.placement = placement;
        tooltip.dataset.arrow = showArrow;

        if (maxWidth) {
            tooltip.style.setProperty('--tooltip-max-width', `${maxWidth}px`);
        }

        // 将 tooltip 插入到 wrapper 中
        wrapper.style.position = 'relative';
        wrapper.appendChild(tooltip);

        return tooltip;
    },

    hideAllClickTooltips() {
        this.tooltips.forEach((tooltip, wrapper) => {
            if (wrapper.dataset.tooltipTrigger === 'click') {
                tooltip.classList.remove('show');
            }
        });
    },

    // 程序化显示/隐藏
    showById(id) {
        const wrapper = document.querySelector(`[id="${id}"]`);
        if (wrapper && wrapper.dataset.tooltip) {
            this.show(wrapper);
        }
    },

    hideById(id) {
        const wrapper = document.querySelector(`[id="${id}"]`);
        if (wrapper) {
            this.hide(wrapper);
        }
    }
};

export default Tooltip;
