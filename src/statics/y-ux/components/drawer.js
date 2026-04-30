import UX from '../core/base.js';

export const Drawer = {
    open(id) {
        const el = UX.getEl(id);
        if (!el) return;
        el.classList.add('ux-drawer-open');
        document.body.style.overflow = 'hidden';
    },
    
    close(id) {
        const el = id ? UX.getEl(id) : document.querySelector('.ux-drawer-open');
        if (!el) return;
        el.classList.remove('ux-drawer-open');
        document.body.style.overflow = '';
    },
    
    init() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-ux-drawer-toggle]');
            if (trigger) {
                return Drawer.open(trigger.getAttribute('data-ux-drawer-toggle'));
            }
            
            const close = e.target.closest('[data-ux-drawer-close]');
            if (close) return Drawer.close();
            
            if (e.target.classList.contains('ux-drawer-overlay')) Drawer.close();
        });
    }
};

export default Drawer;
