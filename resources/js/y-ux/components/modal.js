import UX from '../core/base.js';

export const Modal = {
    open(id) {
        const el = UX.getEl(id);
        if (!el) return;
        el.classList.add('ux-modal-open');
        el.setAttribute('data-visible', 'true');
        document.body.style.overflow = 'hidden';
    },
    
    close(id) {
        const el = id ? UX.getEl(id) : document.querySelector('.ux-modal-open');
        if (!el) return;
        el.classList.remove('ux-modal-open');
        el.removeAttribute('data-visible');
        document.body.style.overflow = '';
    },
    
    init() {
        document.addEventListener('click', (e) => {
            const open = e.target.closest('[data-ux-modal-open]');
            if (open) return Modal.open(open.getAttribute('data-ux-modal-open'));
            
            const close = e.target.closest('[data-ux-modal-close]');
            if (close) return Modal.close(close.getAttribute('data-ux-modal-close'));
            
            if (e.target.classList.contains('ux-modal-backdrop')) Modal.close();
        });
    }
};

export default Modal;
