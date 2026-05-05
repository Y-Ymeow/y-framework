// Transfer 穿梭框组件
const Transfer = {
    init() {
        // 选择项点击
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const item = e.target.closest('.ux-transfer-item');
            if (item && !item.classList.contains('ux-transfer-item-disabled')) {
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox && !checkbox.disabled) {
                    item.classList.toggle('selected');
                    checkbox.checked = item.classList.contains('selected');
                    const transfer = item.closest('.ux-transfer');
                    this.updatePanelState(transfer);
                }
            }

            // 全选/取消全选
            const checkAll = e.target.closest('.ux-transfer-panel-check-all');
            if (checkAll) {
                const panel = checkAll.closest('.ux-transfer-panel');
                const transfer = checkAll.closest('.ux-transfer');
                const isChecked = checkAll.checked;
                
                panel.querySelectorAll('.ux-transfer-item:not(.ux-transfer-item-disabled)').forEach(item => {
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox && !checkbox.disabled) {
                        item.classList.toggle('selected', isChecked);
                        checkbox.checked = isChecked;
                    }
                });
                
                this.updatePanelState(transfer);
            }

            // 左移按钮
            const leftBtn = e.target.closest('.ux-transfer-btn-left');
            if (leftBtn && !leftBtn.disabled) {
                const transfer = leftBtn.closest('.ux-transfer');
                if (transfer) {
                    this.moveLeft(transfer);
                }
            }

            // 右移按钮
            const rightBtn = e.target.closest('.ux-transfer-btn-right');
            if (rightBtn && !rightBtn.disabled) {
                const transfer = rightBtn.closest('.ux-transfer');
                if (transfer) {
                    this.moveRight(transfer);
                }
            }
        });

        // 搜索功能
        document.addEventListener('input', (e) => {
            if (!e.target || !e.target.classList.contains('ux-transfer-panel-search-input')) return;
            
            const searchInput = e.target;
            const panel = searchInput.closest('.ux-transfer-panel');
            const keyword = searchInput.value.toLowerCase();
            
            panel.querySelectorAll('.ux-transfer-item').forEach(item => {
                const label = item.querySelector('.ux-transfer-item-label');
                if (label) {
                    const text = label.textContent.toLowerCase();
                    item.style.display = text.includes(keyword) ? '' : 'none';
                }
            });
        });
    },

    moveRight(transfer) {
        const panels = transfer.querySelectorAll('.ux-transfer-panel');
        const leftPanel = panels[0];
        const rightPanel = panels[1];
        const selected = leftPanel.querySelectorAll('.ux-transfer-item.selected');

        if (selected.length === 0) return;

        const rightList = rightPanel.querySelector('.ux-transfer-panel-list');
        
        selected.forEach(item => {
            item.classList.remove('selected');
            item.dataset.side = 'right';
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = false;
            rightList.appendChild(item);
        });

        // 移除空状态提示
        const rightEmpty = rightList.querySelector('.ux-transfer-panel-empty');
        if (rightEmpty) rightEmpty.remove();

        this.updatePanelState(transfer);
        this.syncToServer(transfer);
    },

    moveLeft(transfer) {
        const panels = transfer.querySelectorAll('.ux-transfer-panel');
        const leftPanel = panels[0];
        const rightPanel = panels[1];
        const selected = rightPanel.querySelectorAll('.ux-transfer-item.selected');

        if (selected.length === 0) return;

        const leftList = leftPanel.querySelector('.ux-transfer-panel-list');
        
        selected.forEach(item => {
            item.classList.remove('selected');
            item.dataset.side = 'left';
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = false;
            leftList.appendChild(item);
        });

        // 移除空状态提示
        const leftEmpty = leftList.querySelector('.ux-transfer-panel-empty');
        if (leftEmpty) leftEmpty.remove();

        this.updatePanelState(transfer);
        this.syncToServer(transfer);
    },

    updatePanelState(transfer) {
        const panels = transfer.querySelectorAll('.ux-transfer-panel');
        const leftPanel = panels[0];
        const rightPanel = panels[1];

        const rightBtn = transfer.querySelector('.ux-transfer-btn-right');
        const leftBtn = transfer.querySelector('.ux-transfer-btn-left');

        // 更新按钮状态
        if (rightBtn) {
            rightBtn.disabled = leftPanel.querySelectorAll('.ux-transfer-item.selected').length === 0;
        }
        if (leftBtn) {
            leftBtn.disabled = rightPanel.querySelectorAll('.ux-transfer-item.selected').length === 0;
        }

        // 更新计数
        this.updateCount(transfer);
        
        // 更新全选框状态
        this.updateCheckAllState(leftPanel);
        this.updateCheckAllState(rightPanel);
    },

    updateCheckAllState(panel) {
        const checkAll = panel.querySelector('.ux-transfer-panel-check-all');
        if (!checkAll) return;

        const items = panel.querySelectorAll('.ux-transfer-item:not(.ux-transfer-item-disabled)');
        const selectedItems = panel.querySelectorAll('.ux-transfer-item.selected:not(.ux-transfer-item-disabled)');
        
        if (items.length === 0) {
            checkAll.checked = false;
            checkAll.indeterminate = false;
        } else if (selectedItems.length === items.length) {
            checkAll.checked = true;
            checkAll.indeterminate = false;
        } else if (selectedItems.length > 0) {
            checkAll.checked = false;
            checkAll.indeterminate = true;
        } else {
            checkAll.checked = false;
            checkAll.indeterminate = false;
        }
    },

    updateCount(transfer) {
        const panels = transfer.querySelectorAll('.ux-transfer-panel');
        panels.forEach(panel => {
            const items = panel.querySelectorAll('.ux-transfer-item');
            const countEl = panel.querySelector('.ux-transfer-panel-count');
            if (countEl) {
                countEl.textContent = items.length;
            }
        });
    },

    syncToServer(transfer) {
        const panels = transfer.querySelectorAll('.ux-transfer-panel');
        const rightPanel = panels[1];
        
        // 收集右侧（目标）的所有 key
        const targetKeys = [];
        rightPanel.querySelectorAll('.ux-transfer-item').forEach(item => {
            const key = item.dataset.key;
            if (key) targetKeys.push(key);
        });

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        transfer.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: JSON.stringify(targetKeys) },
            bubbles: true
        }));
    },

    // 获取当前选中的 keys（用于外部调用）
    getValue(transfer) {
        const panels = transfer.querySelectorAll('.ux-transfer-panel');
        const rightPanel = panels[1];
        
        return Array.from(rightPanel.querySelectorAll('.ux-transfer-item')).map(item => item.dataset.key);
    }
};

export default Transfer;
