// Operations System - Live 操作执行
import { createSafeFragment, replaceLiveHtml, applyLiveFragment } from './core/dom.js';
import { bindNavigateLinks } from './navigate.js';

export function executeOperation(op) {
    switch (op.op) {
        case 'update': {
            const val = String(op.value ?? '');
            let input = document.querySelector(`input[name="${op.target}"], textarea[name="${op.target}"], select[name="${op.target}"]`)
                || document.getElementById(op.target);
            if (input) { 
                input.value = val; 
                input.dispatchEvent(new Event('change', { bubbles: true })); 
            }
            break;
        }
        case 'html': {
            const el = document.querySelector(op.selector);
            if (el) replaceLiveHtml(el, op.html);
            break;
        }
        case 'domPatch': {
            const el = document.querySelector(op.selector);
            if (el) replaceLiveHtml(el, op.html);
            break;
        }
        case 'append': {
            const el = document.querySelector(op.selector);
            if (el) el.appendChild(createSafeFragment(op.html));
            break;
        }
        case 'remove': { 
            const el = document.querySelector(op.selector); 
            if (el) el.remove(); 
            break; 
        }
        case 'addClass': { 
            const el = document.querySelector(op.selector); 
            if (el) el.classList.add(...op.class.split(' ')); 
            break; 
        }
        case 'removeClass': { 
            const el = document.querySelector(op.selector); 
            if (el) el.classList.remove(...op.class.split(' ')); 
            break; 
        }
        case 'openModal': {
            const m = document.querySelector(`[data-ux-modal="${op.id}"]`);
            if (m) { 
                m.setAttribute('data-visible', ''); 
                document.body.style.overflow = 'hidden'; 
            }
            break;
        }
        case 'closeModal': {
            const m = document.querySelector(`[data-ux-modal="${op.id}"]`);
            if (m) { 
                m.removeAttribute('data-visible'); 
                document.body.style.overflow = ''; 
            }
            break;
        }
        case 'redirect':
            if (document.startViewTransition) {
                document.startViewTransition(() => { window.location.href = op.url; });
            } else {
                window.location.href = op.url;
            }
            break;
        case 'reload': 
            window.location.reload(); 
            break;
        case 'js':
            console.warn('Live js operation is disabled for security reasons.');
            break;
        case 'dispatch':
            window.dispatchEvent(new CustomEvent(op.event, { detail: op.detail || {} }));
            break;
    }
}

export function executeOperations(operations) {
    if (!Array.isArray(operations)) return;
    operations.forEach(op => executeOperation(op));
}
