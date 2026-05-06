// Y-Directive - 响应式指令系统入口
import { ReactiveState, effect, batch } from './reactive/index.js';
import { $dispatch } from './scope/index.js';
import { initDirectives } from './runner/index.js';
import { directiveContext } from './reactive/context.js';
import './directives/index.js';

window.$dispatch = $dispatch;

export { ReactiveState, effect, batch, initDirectives };

export function directive(name, handler) {
    if (handler === undefined) {
        return directiveContext.registry.get(name);
    }
    directiveContext.registry.set(name, handler);
}

const componentRegistry = new Map();

export function register(name, definition) {
    componentRegistry.set(name, definition);
}

export function boot(root = document) {
    if (window.__Y_BOOTED__ && root === document) return;
    
    initDirectives(root);

    root.querySelectorAll('[data-component]').forEach(el => {
        if (el._y_component) return;

        const name = el.dataset.component;
        const definition = componentRegistry.get(name);
        if (!definition) return;

        if (!el._y_state) {
            const state = new ReactiveState(definition.state || {});
            el._y_state = state;
        }

        if (definition.connect) definition.connect(el, el._y_state);
        el._y_component = true;
    });

    if (root === document) window.__Y_BOOTED__ = true;
}

const D = {
    boot,
    register,
    directive,
    ReactiveState,
    effect,
    batch,
    initDirectives,
};

window.D = window.Y = D;

export default D;
