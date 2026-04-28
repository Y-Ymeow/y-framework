import UX from '../core/base.js';

export const Accordion = {
    toggle(id, forceOpen = null) {
        const item = UX.getEl(id);
        if (!item) return;
        
        const accordion = item.closest('.ux-accordion');
        const isOpen = forceOpen !== null ? !forceOpen : item.classList.contains('open');

        if (accordion?.dataset.accordionMultiple !== 'true' && !isOpen) {
            accordion.querySelectorAll('.ux-accordion-item').forEach(i => {
                i.classList.remove('open');
                i.querySelector('.ux-accordion-collapse')?.classList.remove('show');
            });
        }

        if (isOpen) {
            item.classList.remove('open');
            item.querySelector('.ux-accordion-collapse')?.classList.remove('show');
        } else {
            item.classList.add('open');
            item.querySelector('.ux-accordion-collapse')?.classList.add('show');
        }
    },
    
    init() {
        document.addEventListener('click', (e) => {
            const header = e.target.closest('.ux-accordion-header');
            if (!header) return;
            Accordion.toggle(header.closest('.ux-accordion-item')?.id);
        });
    }
};

export default Accordion;
