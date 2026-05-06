// DOM State - 从 data-state 创建 ReactiveState
import { ReactiveState } from '../reactive/index.js';

export function createStateFromElement(el) {
    if (el._y_state) return el._y_state;

    try {
        const raw = el.getAttribute('data-state') || el.dataset.state || '{}';
        const state = new ReactiveState(JSON.parse(raw));
        el._y_state = state;
        return state;
    } catch (e) {
        console.error('[y-directive] Invalid data-state on element:', el, e);
        return null;
    }
}

export function getStateFromElement(el) {
    return el._y_state || null;
}
