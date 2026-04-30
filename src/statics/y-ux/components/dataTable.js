// DataTable - 表格多选与批量操作
export class DataTableMultiSelect {
    constructor() {
        this.tables = new Map();
    }

    init(root = document) {
        root.querySelectorAll('table[data-multi-select="true"]').forEach(table => {
            if (table._y_multi_select_bound) return;
            table._y_multi_select_bound = true;

            const tableId = table.dataset.liveId || `table_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
            
            this.tables.set(tableId, {
                table,
                selectedKeys: new Set(),
                totalRows: table.querySelectorAll('tbody .ux-data-table-checkbox').length,
            });

            this.bindCheckboxes(table, tableId);
        });
    }

    bindCheckboxes(table, tableId) {
        const selectAllCheckbox = table.querySelector('.ux-data-table-checkbox-all');
        const rowCheckboxes = table.querySelectorAll('.ux-data-table-checkbox');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleSelectAll(table, tableId, selectAllCheckbox.checked);
            });
        }

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const rowKey = checkbox.dataset.rowKey;
                this.handleRowSelect(table, tableId, rowKey, checkbox.checked);
            });
        });

        const dataTableWrapper = table.closest('.ux-data-table-wrapper');
        if (dataTableWrapper) {
            const cancelBtn = dataTableWrapper.querySelector('.ux-batch-actions-cancel');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.cancelSelection(tableId);
                });
            }

            const triggerBtn = dataTableWrapper.querySelector('.ux-batch-actions-trigger');
            if (triggerBtn) {
                triggerBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleDropdown(dataTableWrapper);
                });
            }

            dataTableWrapper.addEventListener('click', (e) => {
                if (!e.target.closest('.ux-batch-actions-dropdown')) {
                    this.closeDropdown(dataTableWrapper);
                }
            });

            const batchItems = dataTableWrapper.querySelectorAll('.ux-batch-actions-item');
            batchItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.closeDropdown(dataTableWrapper);
                });
            });
        }
    }

    handleSelectAll(table, tableId, checked) {
        const state = this.tables.get(tableId);
        if (!state) return;

        const rowCheckboxes = table.querySelectorAll('.ux-data-table-checkbox');
        
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
            const rowKey = checkbox.dataset.rowKey;
            
            if (checked) {
                state.selectedKeys.add(rowKey);
            } else {
                state.selectedKeys.delete(rowKey);
            }
        });

        this.updateBatchActions(table, state);
    }

    handleRowSelect(table, tableId, rowKey, checked) {
        const state = this.tables.get(tableId);
        if (!state) return;

        if (checked) {
            state.selectedKeys.add(rowKey);
        } else {
            state.selectedKeys.delete(rowKey);
        }

        const selectAllCheckbox = table.querySelector('.ux-data-table-checkbox-all');
        if (selectAllCheckbox) {
            const totalRows = state.totalRows;
            const selectedCount = state.selectedKeys.size;
            selectAllCheckbox.checked = selectedCount > 0;
            selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < totalRows;
        }

        this.updateBatchActions(table, state);
    }

    updateBatchActions(table, state) {
        const batchActionsEl = table.closest('.ux-data-table-wrapper')?.querySelector('.ux-batch-actions');
        if (!batchActionsEl) return;

        const selectedKeys = Array.from(state.selectedKeys);
        const hasSelection = selectedKeys.length > 0;

        if (hasSelection) {
            batchActionsEl.classList.remove('ux-batch-actions-inactive');
            batchActionsEl.classList.add('ux-batch-actions-active');
            batchActionsEl.dataset.selectedKeys = JSON.stringify(selectedKeys);
            batchActionsEl.dataset.selectedCount = selectedKeys.length;

            const countEl = batchActionsEl.querySelector('.ux-batch-actions-count');
            if (countEl) {
                countEl.textContent = `${selectedKeys.length} 项已选择`;
            }

            const emptyEl = batchActionsEl.querySelector('.ux-batch-actions-empty');
            if (emptyEl) {
                emptyEl.style.display = 'none';
            }
        } else {
            batchActionsEl.classList.remove('ux-batch-actions-active');
            batchActionsEl.classList.add('ux-batch-actions-inactive');
            batchActionsEl.dataset.selectedKeys = '[]';
            batchActionsEl.dataset.selectedCount = '0';

            const countEl = batchActionsEl.querySelector('.ux-batch-actions-count');
            if (countEl) {
                countEl.textContent = '';
            }

            const emptyEl = batchActionsEl.querySelector('.ux-batch-actions-empty');
            if (emptyEl) {
                emptyEl.style.display = '';
            }
        }

        const dropdown = batchActionsEl.querySelector('.ux-batch-actions-dropdown');
        if (dropdown) {
            dropdown.style.display = hasSelection ? '' : 'none';
        }

        const cancelBtn = batchActionsEl.querySelector('.ux-batch-actions-cancel');
        if (cancelBtn) {
            cancelBtn.style.display = hasSelection ? '' : 'none';
        }

        const batchItems = batchActionsEl.querySelectorAll('.ux-batch-actions-item');
        batchItems.forEach(item => {
            const params = item.dataset.actionParams;
            if (params) {
                try {
                    const parsed = JSON.parse(params);
                    parsed.selectedKeys = selectedKeys;
                    item.dataset.actionParams = JSON.stringify(parsed);
                } catch (e) {}
            }
        });
    }

    cancelSelection(tableId) {
        const state = this.tables.get(tableId);
        if (!state) return;

        state.selectedKeys.clear();

        const checkboxes = state.table.querySelectorAll('.ux-data-table-checkbox, .ux-data-table-checkbox-all');
        checkboxes.forEach(cb => {
            cb.checked = false;
            cb.indeterminate = false;
        });

        this.updateBatchActions(state.table, state);
    }

    toggleDropdown(dataTableWrapper) {
        const menu = dataTableWrapper.querySelector('.ux-batch-actions-menu');
        if (!menu) return;

        const isVisible = menu.style.display === 'block';
        
        // Close all other menus first
        document.querySelectorAll('.ux-batch-actions-menu').forEach(m => m.style.display = 'none');
        
        menu.style.display = isVisible ? 'none' : 'block';
    }

    closeDropdown(dataTableWrapper) {
        const menu = dataTableWrapper.querySelector('.ux-batch-actions-menu');
        if (menu) {
            menu.style.display = 'none';
        }
    }

    getSelectedKeys(tableId) {
        const state = this.tables.get(tableId);
        return state ? Array.from(state.selectedKeys) : [];
    }
}

export const dataTable = new DataTableMultiSelect();

// 监听局部更新事件
window.addEventListener('y:updated', (e) => {
    const root = e.detail?.el || document;
    dataTable.init(root);
});

export default dataTable;
