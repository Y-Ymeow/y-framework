import UX from '../core/base.js';

export const Tabs = {
    select(tabsId, tabId) {
        const tabsEl = UX.getEl(tabsId);
        if (!tabsEl) return;
        
        const link = tabsEl.querySelector(`[data-tab-target="#${tabId}"], [data-ux-tab-select="${tabId}"]`);
        if (!link) return;

        tabsEl.querySelectorAll('.ux-tabs-item').forEach(i => i.classList.remove('active'));
        link.closest('.ux-tabs-item')?.classList.add('active');

        tabsEl.querySelectorAll('.ux-tabs-pane').forEach(p => p.classList.remove('active', 'show'));
        const pane = tabsEl.querySelector(`#${tabId}`);
        if (pane) pane.classList.add('active', 'show');
    },
    
    init() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('[data-tab-target], [data-ux-tab-select]');
            if (!link) return;
            
            const tabs = link.closest('.ux-tabs');
            const targetId = (link.dataset.tabTarget || link.getAttribute('data-ux-tab-select'))?.replace('#', '');
            if (tabs && targetId) Tabs.select(tabs.id, targetId);
        });
    }
};

export default Tabs;
