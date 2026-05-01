// Collapse 折叠面板组件
const Collapse = {
    init() {
        // 点击头部展开/折叠
        document.addEventListener('click', (e) => {
            const header = e.target.closest('.ux-collapse-header');
            if (header) {
                const collapse = header.closest('.ux-collapse');
                if (collapse && !collapse.classList.contains('ux-collapse-disabled')) {
                    this.toggle(collapse);
                }
            }
        });
    },

    toggle(collapse) {
        const isOpen = collapse.classList.contains('ux-collapse-open');
        if (isOpen) {
            this.close(collapse);
        } else {
            this.open(collapse);
        }
    },

    open(collapse) {
        collapse.classList.add('ux-collapse-open');

        // 触发 action
        const action = collapse.dataset.collapseAction;
        if (action && window.L) {
            window.L.executeOperation({
                op: 'action',
                action: action,
                params: { open: true }
            });
        }

        // 触发自定义事件
        collapse.dispatchEvent(new CustomEvent('collapse:open'));
    },

    close(collapse) {
        collapse.classList.remove('ux-collapse-open');

        // 触发 action
        const action = collapse.dataset.collapseAction;
        if (action && window.L) {
            window.L.executeOperation({
                op: 'action',
                action: action,
                params: { open: false }
            });
        }

        // 触发自定义事件
        collapse.dispatchEvent(new CustomEvent('collapse:close'));
    },

    // 程序化控制
    openById(id) {
        const collapse = document.querySelector(`#${id}.ux-collapse`);
        if (collapse) {
            this.open(collapse);
        }
    },

    closeById(id) {
        const collapse = document.querySelector(`#${id}.ux-collapse`);
        if (collapse) {
            this.close(collapse);
        }
    },

    toggleById(id) {
        const collapse = document.querySelector(`#${id}.ux-collapse`);
        if (collapse) {
            this.toggle(collapse);
        }
    }
};

export default Collapse;
