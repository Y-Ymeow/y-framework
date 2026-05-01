// Popover 气泡卡片组件
const Popover = {
    popovers: new Map(),

    init() {
        // 点击触发
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-popover-trigger="click"]');
            if (wrapper) {
                e.stopPropagation();
                this.toggle(wrapper);
            } else {
                // 点击其他地方关闭 popover
                this.hideAll();
            }
        });

        // Hover 触发
        document.addEventListener('mouseenter', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-popover-trigger="hover"]');
            if (wrapper) {
                this.show(wrapper);
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-popover-trigger="hover"]');
            if (wrapper) {
                this.hide(wrapper);
            }
        }, true);

        // Focus 触发
        document.addEventListener('focus', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-popover-trigger="focus"]');
            if (wrapper) {
                this.show(wrapper);
            }
        }, true);

        document.addEventListener('blur', (e) => {
            if (!e.target || !e.target.closest) return;
            const wrapper = e.target.closest('[data-popover-trigger="focus"]');
            if (wrapper) {
                this.hide(wrapper);
            }
        }, true);
    },

    show(wrapper) {
        let popover = this.popovers.get(wrapper);

        if (!popover) {
            popover = this.createPopover(wrapper);
            this.popovers.set(wrapper, popover);
        }

        popover.classList.add('show');

        // 触发自定义事件
        wrapper.dispatchEvent(new CustomEvent('popover:show'));
    },

    hide(wrapper) {
        const popover = this.popovers.get(wrapper);
        if (popover) {
            popover.classList.remove('show');
            wrapper.dispatchEvent(new CustomEvent('popover:hide'));
        }
    },

    toggle(wrapper) {
        const popover = this.popovers.get(wrapper);
        if (popover && popover.classList.contains('show')) {
            this.hide(wrapper);
        } else {
            this.show(wrapper);
        }
    },

    hideAll() {
        this.popovers.forEach((popover, wrapper) => {
            if (popover.classList.contains('show')) {
                this.hide(wrapper);
            }
        });
    },

    createPopover(wrapper) {
        const title = wrapper.dataset.popoverTitle;
        const content = wrapper.dataset.popoverContent;
        const placement = wrapper.dataset.popoverPlacement || 'top';
        const showArrow = wrapper.dataset.popoverArrow !== 'false';
        const maxWidth = wrapper.dataset.popoverMaxWidth;

        const popover = document.createElement('div');
        popover.className = 'ux-popover';
        popover.dataset.placement = placement;
        popover.dataset.arrow = showArrow;

        if (maxWidth) {
            popover.style.setProperty('--popover-max-width', `${maxWidth}px`);
        }

        // 标题
        if (title) {
            const titleEl = document.createElement('div');
            titleEl.className = 'ux-popover-title';
            titleEl.textContent = title;
            popover.appendChild(titleEl);
            popover.dataset.hasTitle = 'true';
        }

        // 内容
        if (content) {
            const contentEl = document.createElement('div');
            contentEl.className = 'ux-popover-content';
            contentEl.innerHTML = content;
            popover.appendChild(contentEl);
        }

        // 将 popover 插入到 wrapper 中
        wrapper.style.position = 'relative';
        wrapper.appendChild(popover);

        return popover;
    },

    // 程序化控制
    showById(id) {
        const wrapper = document.querySelector(`#${id}.ux-popover-wrapper`);
        if (wrapper) {
            this.show(wrapper);
        }
    },

    hideById(id) {
        const wrapper = document.querySelector(`#${id}.ux-popover-wrapper`);
        if (wrapper) {
            this.hide(wrapper);
        }
    }
};

export default Popover;
