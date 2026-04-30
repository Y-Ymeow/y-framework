import UX from '../core/base.js';

export const Dropdown = {
    toggle(id) {
        const el = UX.getEl(id);
        if (!el) return;
        el.classList.toggle('ux-open');
    },
    
    closeAll() {
        document.querySelectorAll('.ux-dropdown.ux-open').forEach(d => d.classList.remove('ux-open'));
    },
    
    init() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-ux-dropdown-toggle]');
            if (trigger) {
                this.toggle(trigger.getAttribute('data-ux-dropdown-toggle') || trigger.closest('.ux-dropdown')?.id);
                return;
            }
            if (!e.target.closest('.ux-dropdown')) this.closeAll();
        });
    }
};

export default Dropdown;
