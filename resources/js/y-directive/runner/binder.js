import { ReactiveState, effect } from '../reactive/index.js';
import { execute, evaluate } from '../evaluator/index.js';
import { findParentState } from '../dom/index.js';
import { matchDirectives } from '../directives/matcher.js';
import { directiveContext } from '../reactive/context.js';

function directive(name, handler) {
    if (handler === undefined) {
        return directiveContext.registry.get(name);
    }
    directiveContext.registry.set(name, handler);
}

export function bindDirective(el, state, match) {
    const handler = directive(match.type);
    if (!handler) return;

    const options = {
        args: match.args,
        modifiers: match.modifiers,
        content: match.content,
        attrName: match.name,
        effect: effect,
        // execute 用于求值
        execute: (expr, s) => evaluate(expr, s || state, el),
        // $execute 用于执行带副作用的表达式，支持 event 注入
        $execute: (expr, s, ev) => execute(expr, s || state, ev, el),
        $evaluate: (expr, s) => evaluate(expr, s || state, el),
        cleanup: (fn) => {
            if (!el._y_cleanups) el._y_cleanups = [];
            el._y_cleanups.push(fn);
        }
    };

    const cleanup = handler(el, state, match.method, options);
    
    if (cleanup && typeof cleanup === 'function') {
        options.cleanup(cleanup);
    }

    return cleanup;
}

export function initDirectives(root = document) {
    const rootEl = root === document ? document.documentElement : root;
    if (!rootEl) return;

    initStates(rootEl);
    bindDirectives(rootEl);
    handleCloak(rootEl);
    
    // 处理 data-loading 渐显
    const loadingEls = [];
    if (rootEl.hasAttribute && rootEl.hasAttribute('data-loading')) {
        loadingEls.push(rootEl);
    }
    if (rootEl.querySelectorAll) {
        rootEl.querySelectorAll('[data-loading]').forEach(el => loadingEls.push(el));
    }

    if (loadingEls.length > 0) {
        requestAnimationFrame(() => {
            loadingEls.forEach(el => el.classList.add('y-loaded'));
        });
    }

    const event = new CustomEvent('y:initialized', { detail: { root: rootEl } });
    document.dispatchEvent(event);
}

function initStates(root) {
    const allStateEls = [];
    if (root.hasAttribute && root.hasAttribute('data-state')) {
        allStateEls.push(root);
    }
    
    if (root.querySelectorAll) {
        root.querySelectorAll('[data-state]').forEach(el => allStateEls.push(el));
    }

    allStateEls.forEach(el => {
        if (el._y_state) return;
        try {
            const raw = el.getAttribute('data-state') || '{}';
            el._y_state = new ReactiveState(JSON.parse(raw));
        } catch (e) {
            console.error('[y-directive] Invalid data-state:', el, e);
        }
    });
}

function bindDirectives(root) {
    const els = [];
    
    // 1. 检查根节点本身
    if (root.nodeType === Node.ELEMENT_NODE && hasDirective(root)) {
        els.push(root);
    }
    
    // 2. 扫描所有子节点
    if (root.querySelectorAll) {
        root.querySelectorAll('*').forEach(el => {
            if (hasDirective(el)) {
                els.push(el);
            }
        });
    }

    els.forEach(el => processElement(el));
}

function hasDirective(el) {
    if (!el.attributes) return false;
    for (let i = 0; i < el.attributes.length; i++) {
        if (el.attributes[i].name.startsWith('data-')) {
            return true;
        }
    }
    return false;
}

function processElement(el) {
    if (el._y_directive_bound) return;

    // 跳过黑名单标签
    const tag = el.tagName.toLowerCase();
    if (tag === 'script' || tag === 'style' || tag === 'template') return;

    const state = findParentState(el);
    if (!state) return;

    const matches = matchDirectives(el);
    if (matches.length > 0) {
        matches.forEach(match => {
            const cleanup = bindDirective(el, state, match);
            if (cleanup) {
                if (!el._y_cleanups) el._y_cleanups = [];
                el._y_cleanups.push(cleanup);
            }
        });
        el._y_directive_bound = true;
    }
}

function handleCloak(root) {
    const cloakEls = root.querySelectorAll('[data-cloak]');
    cloakEls.forEach(el => el.removeAttribute('data-cloak'));

    if (root.hasAttribute && root.hasAttribute('data-cloak')) {
        root.removeAttribute('data-cloak');
    }
}
