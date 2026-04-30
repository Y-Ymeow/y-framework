// UX Base - 基础工具
export const UX = {
    getEl(id) { 
        return typeof id === 'string' ? document.getElementById(id) : id; 
    },
    
    safeAttrs: [
        'data-ux-dropdown-toggle',
        'data-ux-modal-open',
        'data-ux-modal-close',
        'data-ux-drawer-toggle',
        'data-ux-drawer-close',
        'data-ux-tab-select',
        'data-ux-toast-close',
        'data-tab-target',
        'data-accordion-multiple',
        'data-ux-chart',
        'data-chart-config',
        'data-action',
        'data-editor',
        'data-input-id',
        'data-placeholder',
    ],

    registerSafeAttrs() {
        const set = window.Y_UI_SAFE_ATTRS;
        if (!set) return;
        this.safeAttrs.forEach(attr => set.add(attr));
    },
};

export default UX;
