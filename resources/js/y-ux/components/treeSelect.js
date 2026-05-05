// TreeSelect 树形选择组件
const TreeSelect = {
    selects: new Map(),

    init() {
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const selector = e.target.closest('.ux-tree-select-selector');
            if (selector) {
                const treeSelect = selector.closest('.ux-tree-select');
                if (treeSelect) {
                    this.toggle(treeSelect);
                }
            }

            const toggle = e.target.closest('.ux-tree-select-node-toggle');
            if (toggle) {
                const node = toggle.closest('.ux-tree-select-node');
                if (node) {
                    this.toggleNode(node);
                }
            }

            const content = e.target.closest('.ux-tree-select-node-content');
            if (content) {
                const node = content.closest('.ux-tree-select-node');
                const treeSelect = content.closest('.ux-tree-select');
                if (node && treeSelect) {
                    this.selectNode(treeSelect, node);
                }
            }

            if (!e.target.closest('.ux-tree-select')) {
                this.hideAll();
            }
        });
    },

    toggle(treeSelect) {
        if (treeSelect.classList.contains('ux-tree-select-open')) {
            this.hide(treeSelect);
        } else {
            this.show(treeSelect);
        }
    },

    show(treeSelect) {
        this.hideAll();

        let dropdown = treeSelect.querySelector('.ux-tree-select-dropdown');
        if (!dropdown) {
            dropdown = this.createDropdown(treeSelect);
        }

        treeSelect.classList.add('ux-tree-select-open');
        dropdown.classList.add('show');
    },

    hide(treeSelect) {
        treeSelect.classList.remove('ux-tree-select-open');
        const dropdown = treeSelect.querySelector('.ux-tree-select-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    },

    hideAll() {
        document.querySelectorAll('.ux-tree-select-open').forEach(select => {
            this.hide(select);
        });
    },

    createDropdown(treeSelect) {
        const dropdown = document.createElement('div');
        dropdown.className = 'ux-tree-select-dropdown';
        dropdown.innerHTML = '<div class="ux-tree-select-tree">树形结构待实现</div>';
        treeSelect.appendChild(dropdown);
        return dropdown;
    },

    toggleNode(node) {
        const children = node.querySelector('.ux-tree-select-children');
        if (children) {
            children.classList.toggle('collapsed');
            const toggle = node.querySelector('.ux-tree-select-node-toggle');
            if (toggle) {
                toggle.classList.toggle('expanded');
            }
        }
    },

    selectNode(treeSelect, node) {
        const value = node.dataset.nodeValue;
        const title = node.querySelector('.ux-tree-select-node-title');
        if (title) {
            const display = treeSelect.querySelector('.ux-tree-select-display');
            if (display) {
                display.textContent = title.textContent;
                display.classList.remove('placeholder');
            }
        }

        // 更新值
        treeSelect.dataset.treeValue = value || '';

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        treeSelect.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: value || '' },
            bubbles: true
        }));

        this.hide(treeSelect);
    },

    // 程序化设置值
    setValue(treeSelect, value) {
        treeSelect.dataset.treeValue = value;
        const display = treeSelect.querySelector('.ux-tree-select-display');
        if (display && value) {
            const node = treeSelect.querySelector(`[data-node-value="${value}"]`);
            if (node) {
                const title = node.querySelector('.ux-tree-select-node-title');
                if (title) {
                    display.textContent = title.textContent;
                    display.classList.remove('placeholder');
                }
            }
        }
    }
};

export default TreeSelect;
