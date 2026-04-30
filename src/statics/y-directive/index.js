// Y-Directive - 响应式指令系统入口
import { ReactiveState, effect, batch } from './reactive.js';
import { initDirectives, evaluate, execute } from './directives.js';

const registry = new Map();

function register(name, definition) {
    registry.set(name, definition);
}

function boot(root = document) {
    initDirectives(root);

    root.querySelectorAll('[data-component]').forEach(el => {
        if (el._y_component) return;
        const name = el.dataset.component;
        const definition = registry.get(name);
        if (!definition) return;

        if (!el._y_state) {
            const state = new ReactiveState(definition.state || {});
            el._y_state = state;
        }

        if (definition.connect) definition.connect(el, el._y_state);
        el._y_component = true;
    });
}

const D = {
    boot,
    register,
    ReactiveState,
    effect,
    batch,
    evaluate,
    execute,
};

window.D = D;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => D.boot());
} else {
    D.boot();
}

export default D;
