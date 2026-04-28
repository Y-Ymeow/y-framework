import { ReactiveState, effect, batch } from './reactive.js';

const exprCache = new Map();

// Security: Forbidden patterns in expressions
const FORBIDDEN_PATTERNS = [
    /\beval\b/,
    /\bFunction\b/,
    /\bwindow\.location\s*=/,      // 禁止重定向
    /\bwindow\.location\.href\s*=/,
    /\bwindow\.open\b/,
    /\bdocument\.write\b/,
    /\bdocument\.writeln\b/,
    /\bdocument\.cookie\s*=/,     // 禁止修改 cookie
    /\bdocument\.domain\s*=/,
    /\bdocument\.location\s*=/,
    /\blocalStorage\.(?!setItem|getItem|removeItem|clear)\w/, // 只允许特定方法
    /\bsessionStorage\.(?!setItem|getItem|removeItem|clear)\w/,
    /\bfetch\b/,
    /\bXMLHttpRequest\b/,
    /\bWebSocket\b/,
    /\bWorker\b/,
    /\bimport\b/,
    /\brequire\b/,
    /\b__proto__\b/,
    /\bconstructor\b/,
    /\bprototype\b/,
    /\.\.\./,
    /<script/i,
    /javascript:/i,
    /on\w+\s*=/,
];

function validateExpression(expr) {
    for (const pattern of FORBIDDEN_PATTERNS) {
        if (pattern.test(expr)) {
            console.error('Security: Forbidden expression pattern detected:', expr);
            throw new Error('Forbidden expression pattern');
        }
    }
}

function $dispatch(eventName, detail) {
    window.dispatchEvent(new CustomEvent(eventName, { detail: detail || {} }));
}

function evaluate(expr, state, el) {
    try {
        validateExpression(expr);
        if (!exprCache.has(expr)) {
            exprCache.set(expr, new Function('$', '$dispatch', '$watch', '$root', '$el', '$nextTick', '$refs', `with($) { return (${expr}) }`));
        }
        const rootEl = el?.closest('[data-state]');
        const rootState = rootEl?._y_state;
        return exprCache.get(expr)(
            state.proxy,
            $dispatch,
            (key, cb) => $watch(state, key, cb),
            rootState ? rootState.proxy : state.proxy,
            el,
            (cb) => $nextTick(cb),
            state.proxy.$refs || {}
        );
    } catch (e) {
        console.warn('Evaluate error:', expr, e);
        return undefined;
    }
}

function execute(expr, state, event, el) {
    try {
        validateExpression(expr);
        const fn = new Function('$', '$event', '$dispatch', '$watch', '$root', '$el', '$nextTick', '$refs', `with($) { ${expr} }`);
        const rootEl = el?.closest('[data-state]');
        const rootState = rootEl?._y_state;
        fn(
            state.proxy,
            event,
            $dispatch,
            (key, cb) => $watch(state, key, cb),
            rootState ? rootState.proxy : state.proxy,
            el,
            (cb) => $nextTick(cb),
            state.proxy.$refs || {}
        );
    } catch (e) {
        console.warn('Execute error:', expr, e);
    }
}

function $watch(state, key, callback) {
    let oldVal = state.proxy[key];
    return effect(() => {
        const newVal = state.proxy[key];
        if (!Object.is(oldVal, newVal)) {
            const prev = oldVal;
            oldVal = newVal;
            callback(newVal, prev);
        }
    });
}

function $nextTick(callback) {
    queueMicrotask(() => {
        requestAnimationFrame(() => {
            callback();
        });
    });
}

export function initDirectives(root = document) {
    root.querySelectorAll('[data-state]').forEach(el => {
        if (el._y_state) return;
        initScope(el);
    });

    initGlobalEventHandlers(root);
}

function initGlobalEventHandlers(root) {
    const processEl = (el) => {
        for (const attr of el.attributes) {
            const name = attr.name;
            if (name.startsWith('data-on:')) {
                const afterOn = name.slice(8);
                bindGlobalOn(el, afterOn, attr.value);
            }
        }
    };

    if (root.querySelectorAll) {
        root.querySelectorAll('[data-on\\:click], [data-on\\:submit], [data-on\\:change], [data-on\\:input]').forEach(el => {
            if (el._y_global_bound) return;
            el._y_global_bound = true;
            processEl(el);
        });
    }
}

function bindGlobalOn(el, eventSpec, expr) {
    const parts = eventSpec.split('.');
    const eventName = parts[0];
    const modifiers = parts.slice(1);

    validateExpression(expr);

    const handler = (e) => {
        if (eventName === 'submit' || modifiers.includes('prevent')) e.preventDefault();
        if (modifiers.includes('stop')) e.stopPropagation();
        if (modifiers.includes('self') && e.target !== el) return;
        if (modifiers.includes('once')) el.removeEventListener(eventName, handler);

        try {
            const fn = new Function('$event', '$dispatch', `with(window) { ${expr} }`);
            fn(e, $dispatch);
        } catch (err) {
            console.warn('Global event handler error:', expr, err);
        }
    };

    el.addEventListener(eventName, handler);
}

function initScope(el) {
    let data = {};
    const raw = el.dataset.state;
    if (raw) {
        try { data = JSON.parse(raw); }
        catch (e) { try { data = (new Function(`return (${raw})`))(); } catch (e2) { data = {}; } }
    }

    data.$refs = {};

    const state = new ReactiveState(data);
    state._rootEl = el;
    el._y_state = state;

    processTree(el, state);

    if (el.hasAttribute('data-live')) {
        el._y_live = true;
    }
}

function processTree(root, state) {
    processElement(root, state);

    for (const child of root.children) {
        if (child.hasAttribute('data-state') && child !== root) {
            if (!child._y_state) initScope(child);
            continue;
        }
        processTree(child, state);
    }
}

function processElement(el, state) {
    const ds = el.dataset;

    if (ds.transition !== undefined) bindTransition(el, ds.transition, state);
    if (ds.text !== undefined) bindText(el, ds.text, state);
    if (ds.html !== undefined) bindHtml(el, ds.html, state);
    if (ds.show !== undefined) bindShow(el, ds.show, state);
    if (ds.if !== undefined) bindIf(el, ds.if, state);
    if (ds.model !== undefined) bindModel(el, ds.model, state);
    if (ds.for !== undefined) bindFor(el, ds.for, state);
    if (ds.effect !== undefined) bindEffect(el, ds.effect, state);
    if (ds.ref !== undefined) bindRef(el, ds.ref, state);
    if (ds.cloak !== undefined) el.removeAttribute('data-cloak');
    if (ds.bind !== undefined) bindDataBind(el, ds.bind, state, 'text');
    if (ds.bindValue !== undefined) bindDataBind(el, ds.bindValue, state, 'value');
    if (ds.bindHtml !== undefined) bindDataBind(el, ds.bindHtml, state, 'html');
    if (ds.bindHref !== undefined) bindDataBind(el, ds.bindHref, state, 'href');
    if (ds.bindSrc !== undefined) bindDataBind(el, ds.bindSrc, state, 'src');
    if (ds.bindDisabled !== undefined) bindDataBind(el, ds.bindDisabled, state, 'disabled');
    if (ds.bindChecked !== undefined) bindDataBind(el, ds.bindChecked, state, 'checked');
    if (ds.action !== undefined) {}

    for (const attr of el.attributes) {
        const name = attr.name;
        if (name.startsWith('data-on:')) {
            const afterOn = name.slice(8);
            bindOn(el, afterOn, attr.value, state);
        } else if (name.startsWith('data-bind-')) {
            const attrName = name.slice(10);
            if (!['value','html','href','src','disabled','checked'].includes(attrName)) {
                bindAttr(el, attrName, attr.value, state);
            }
        } else if (name === 'data-class') {
            bindClass(el, attr.value, state);
        }
    }
}

function bindText(el, expr, state) {
    state.addDisposer(effect(() => { el.textContent = evaluate(expr, state, el) ?? ''; }));
}

function bindHtml(el, expr, state) {
    state.addDisposer(effect(() => {
        const html = evaluate(expr, state, el) ?? '';
        el.innerHTML = sanitizeHtml(html);
    }));
}

// Security: Strip script tags from HTML
function sanitizeHtml(html) {
    if (typeof html !== 'string') return html;
    return html.replace(/<script\b[^>]*>(.*?)<\/script>/gis, '');
}

function bindShow(el, expr, state) {
    if (!el._y_original_display) {
        el._y_original_display = getComputedStyle(el).display === 'none' ? '' : (el.style.display || '');
    }

    state.addDisposer(effect(() => {
        const visible = !!evaluate(expr, state, el);
        if (visible === el._y_visible) return;
        el._y_visible = visible;

        if (visible) {
            showWithTransition(el);
        } else {
            hideWithTransition(el);
        }
    }));
}

function bindClass(el, expr, state) {
    const staticClass = el.getAttribute('class') || '';
    el.setAttribute('data-orig-class', staticClass);

    state.addDisposer(effect(() => {
        const value = evaluate(expr, state, el);
        let dynamicClasses = '';

        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            dynamicClasses = Object.entries(value).filter(([, v]) => v).map(([k]) => k).join(' ');
        } else if (Array.isArray(value)) {
            dynamicClasses = value.filter(Boolean).join(' ');
        } else if (typeof value === 'string') {
            dynamicClasses = value;
        }

        el.setAttribute('class', staticClass + (dynamicClasses ? ' ' + dynamicClasses : ''));
    }));
}

function bindIf(el, expr, state) {
    const placeholder = document.createComment('if');
    const parent = el.parentNode;
    const clone = el.cloneNode(true);
    clone.removeAttribute('data-if');
    let currentNode = null;

    parent.insertBefore(placeholder, el);
    el.remove();

    state.addDisposer(effect(() => {
        const show = evaluate(expr, state, el);
        if (show && !currentNode) {
            const node = clone.cloneNode(true);
            parent.insertBefore(node, placeholder);
            processTree(node, state);
            currentNode = node;

            if (node._y_transition) {
                node._y_original_display = getComputedStyle(node).display === 'none' ? '' : (node.style.display || '');
                node._y_visible = true;
                showWithTransition(node);
            }
        } else if (!show && currentNode) {
            const target = currentNode;
            currentNode = null;

            if (target._y_transition) {
                hideWithTransition(target, () => target.remove());
            } else {
                target.remove();
            }
        }
    }));
}

function bindTransition(el, expr, state) {
    state.addDisposer(effect(() => {
        const value = evaluate(expr, state, el);
        el._y_transition = normalizeTransition(value);
    }));
}

function normalizeTransition(value) {
    if (!value || typeof value !== 'object') return null;

    const base = {
        duration: value.duration,
        delay: value.delay,
        easing: value.easing,
    };

    return {
        enter: normalizeTransitionPhase(value.enter ?? value.show ?? {}, base, {
            from: value.enterFrom ?? value.from,
            to: value.enterTo ?? value.to,
        }),
        leave: normalizeTransitionPhase(value.leave ?? value.hide ?? {}, base, {
            from: value.leaveFrom,
            to: value.leaveTo,
        }),
    };
}

function normalizeTransitionPhase(phase, base, fallback = {}) {
    const source = (phase && typeof phase === 'object') ? phase : {};
    return {
        duration: source.duration ?? base.duration ?? 200,
        delay: source.delay ?? base.delay ?? 0,
        easing: source.easing ?? base.easing ?? 'ease',
        from: source.from ?? fallback.from ?? {},
        to: source.to ?? fallback.to ?? {},
    };
}

function showWithTransition(el, done) {
    const transition = el._y_transition?.enter;
    cancelTransition(el);

    el.style.display = el._y_original_display ?? '';

    if (!transition || isEmptyTransition(transition)) {
        done?.();
        return;
    }

    runTransition(el, transition, done);
}

function hideWithTransition(el, done) {
    const transition = el._y_transition?.leave;
    cancelTransition(el);

    if (!transition || isEmptyTransition(transition)) {
        el.style.display = 'none';
        done?.();
        return;
    }

    runTransition(el, transition, () => {
        el.style.display = 'none';
        done?.();
    });
}

function runTransition(el, config, done) {
    const previousStyle = el.getAttribute('style') || '';
    const cleanup = () => {
        if (el._y_transition_frame) cancelAnimationFrame(el._y_transition_frame);
        if (el._y_transition_timeout) clearTimeout(el._y_transition_timeout);
        el._y_transition_frame = null;
        el._y_transition_timeout = null;
        el._y_transition_cleanup = null;

        const display = el.style.display;
        el.setAttribute('style', previousStyle);
        if (display) el.style.display = display;
        done?.();
    };

    el._y_transition_cleanup = cleanup;

    applyStyleMap(el, config.from);
    forceReflow(el);

    el._y_transition_frame = requestAnimationFrame(() => {
        el.style.transitionProperty = collectTransitionProps(config).join(', ') || 'all';
        el.style.transitionDuration = `${Number(config.duration) || 0}ms`;
        el.style.transitionDelay = `${Number(config.delay) || 0}ms`;
        el.style.transitionTimingFunction = config.easing || 'ease';
        applyStyleMap(el, config.to);

        const total = (Number(config.duration) || 0) + (Number(config.delay) || 0);
        el._y_transition_timeout = setTimeout(cleanup, total + 34);
    });
}

function cancelTransition(el) {
    if (typeof el._y_transition_cleanup === 'function') {
        const cleanup = el._y_transition_cleanup;
        el._y_transition_cleanup = null;
        cleanup();
    }
}

function applyStyleMap(el, styles) {
    if (!styles || typeof styles !== 'object') return;
    for (const [key, value] of Object.entries(styles)) {
        el.style[key] = value;
    }
}

function collectTransitionProps(config) {
    return Array.from(new Set([
        ...Object.keys(config.from || {}),
        ...Object.keys(config.to || {}),
    ])).map(camelToKebab);
}

function camelToKebab(key) {
    return key.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`);
}

function isEmptyTransition(config) {
    return Object.keys(config.from || {}).length === 0 && Object.keys(config.to || {}).length === 0;
}

function forceReflow(el) {
    void el.offsetHeight;
}

function bindModel(el, key, state) {
    const parts = key.split('.');
    const baseKey = parts[0];
    const modifiers = parts.slice(1);
    const isLazy = modifiers.includes('lazy');
    const isNumber = modifiers.includes('number');
    const isBoolean = modifiers.includes('boolean');

    state.addDisposer(effect(() => {
        const val = state.proxy[baseKey];
        if (val === undefined) return;
        if (el.type === 'checkbox') el.checked = !!val;
        else if (el.type === 'radio') el.checked = el.value == val;
        else if (el.tagName === 'SELECT') {
            Array.from(el.options).forEach(o => o.selected = Array.isArray(val) ? val.includes(o.value) : o.value == val);
        } else {
            if (document.activeElement !== el) el.value = val ?? '';
        }
    }));

    el.addEventListener(isLazy ? 'change' : 'input', () => {
        let val;
        if (el.type === 'checkbox') val = el.checked;
        else if (el.type === 'number' || el.type === 'range' || isNumber) val = Number(el.value);
        else if (isBoolean) val = el.value === 'true';
        else val = el.value;
        state.proxy[baseKey] = val;
    });
}

function bindFor(el, expr, state) {
    if (el.tagName !== 'TEMPLATE') {
        console.warn('data-for must be on a <template> element, got:', el.tagName, el);
        return;
    }

    const match = expr.match(/^(?:\((\w+),\s*(\w+)\)|(\w+))\s+in\s+(.+)$/);
    if (!match) return;

    const itemVar = match[1] || match[3];
    const indexVar = match[2] || null;
    const listExpr = match[4].trim();

    const parent = el.parentNode;
    const placeholder = document.createComment(`for: ${expr}`);
    parent.insertBefore(placeholder, el);

    let currentNodes = [];

    state.addDisposer(effect(() => {
        const list = evaluate(listExpr, state, el);
        if (!Array.isArray(list)) {
            currentNodes.forEach(n => n.remove());
            currentNodes = [];
            return;
        }

        const fragment = document.createDocumentFragment();
        const newNodes = [];

        list.forEach((item, index) => {
            const content = el.content.cloneNode(true);
            const wrapper = document.createElement('div');
            wrapper.appendChild(content);
            const node = wrapper.firstElementChild || wrapper;

            const childState = new ReactiveState({ [itemVar]: item });
            if (indexVar) childState.proxy[indexVar] = index;
            childState._parent = state;
            node._y_state = childState;

            fillTemplate(node, item, index, itemVar, indexVar);
            processTree(node, childState);
            fragment.appendChild(node);
            newNodes.push(node);
        });

        currentNodes.forEach(n => n.remove());
        parent.insertBefore(fragment, placeholder);
        currentNodes = newNodes;
    }));
}

function fillTemplate(node, item, index, itemKey, indexKey) {
    const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    while (walker.nextNode()) textNodes.push(walker.currentNode);

    textNodes.forEach(tn => {
        tn.textContent = tn.textContent
            .replace(new RegExp(`\\{\\{\\s*${itemKey}\\s*\\}\\}`, 'g'), item)
            .replace(new RegExp(`\\{\\{\\s*${indexKey}\\s*\\}\\}`, 'g'), index);
    });
}

function bindOn(el, eventSpec, expr, state) {
    const parts = eventSpec.split('.');
    const eventName = parts[0];
    const modifiers = parts.slice(1);

    const isCustom = isCustomEventName(eventName);

    if (isCustom) {
        bindCustomEvent(el, eventName, expr, state, modifiers);
    } else {
        bindNativeEvent(el, eventName, expr, state, modifiers);
    }
}

function isCustomEventName(name) {
    const nativeEvents = ['click','dblclick','mousedown','mouseup','mouseover','mouseout','mousemove',
        'keydown','keyup','keypress','focus','blur','change','input','submit','reset',
        'scroll','resize','load','unload','error','contextmenu','wheel','drag','dragstart',
        'dragend','dragover','dragenter','dragleave','drop','touchstart','touchmove','touchend',
        'pointerdown','pointerup','pointermove','animationstart','animationend','transitionend'];

    return !nativeEvents.includes(name.toLowerCase());
}

function bindNativeEvent(el, eventName, expr, state, modifiers) {
    const handler = (e) => {
        if (modifiers.includes('prevent')) e.preventDefault();
        if (modifiers.includes('stop')) e.stopPropagation();
        if (modifiers.includes('self') && e.target !== el) return;
        if (modifiers.includes('once')) el.removeEventListener(eventName, handler);

        execute(expr, state, e, el);
    };

    if (modifiers.includes('outside')) {
        document.addEventListener(eventName, (e) => {
            if (!el.contains(e.target)) execute(expr, state, e, el);
        });
    } else {
        el.addEventListener(eventName, handler);
    }
}

function bindCustomEvent(el, eventName, expr, state, modifiers) {
    window.addEventListener(eventName, (e) => {
        const detail = e.detail || {};
        const eventProxy = new Proxy(detail, {
            get(target, prop) {
                if (prop in target) return target[prop];
                if (prop in e) return typeof e[prop] === 'function' ? e[prop].bind(e) : e[prop];
                return undefined;
            }
        });
        execute(expr, state, eventProxy, el);
    });
}

function bindAttr(el, attr, expr, state) {
    if (attr === 'class') {
        bindClass(el, expr, state);
        return;
    }

    state.addDisposer(effect(() => {
        const value = evaluate(expr, state, el);

        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            for (const [key, val] of Object.entries(value)) {
                if (val) {
                    el.setAttribute(key, '');
                } else {
                    el.removeAttribute(key);
                }
            }
        } else if (value === false || value === null || value === undefined) {
            el.removeAttribute(attr);
        } else if (value === true) {
            el.setAttribute(attr, '');
        } else {
            el.setAttribute(attr, String(value));
        }
    }));
}

function bindEffect(el, expr, state) {
    state.addDisposer(effect(() => execute(expr, state, null, el)));
}

function bindRef(el, name, state) {
    state.proxy.$refs[name] = el;
}

function bindDataBind(el, key, state, type = 'text') {
    state.addDisposer(effect(() => {
        const val = state.proxy[key];
        if (val === undefined) return;
        switch (type) {
            case 'text': el.textContent = val ?? ''; break;
            case 'value': if (document.activeElement !== el) el.value = val ?? ''; break;
            case 'html': el.innerHTML = sanitizeHtml(val ?? ''); break;
            case 'href': el.setAttribute('href', val ?? ''); break;
            case 'src': el.setAttribute('src', val ?? ''); break;
            case 'disabled': el.disabled = !!val; break;
            case 'checked': el.checked = !!val; break;
        }
    }));
}

// Security: Safe event registration registry
const safeEventRegistry = new Map();

/**
 * Register a safe event handler
 * @param {string} name - Event handler name
 * @param {Function} handler - Safe handler function
 */
export function registerEventHandler(name, handler) {
    if (typeof handler !== 'function') {
        throw new Error('Event handler must be a function');
    }
    safeEventRegistry.set(name, handler);
}

/**
 * Execute a registered event handler
 * @param {string} name - Event handler name
 * @param {Event} event - Event object
 * @param {Object} context - Additional context
 */
export function executeEventHandler(name, event, context = {}) {
    const handler = safeEventRegistry.get(name);
    if (handler) {
        try {
            handler(event, context);
        } catch (e) {
            console.error('Event handler error:', name, e);
        }
    } else {
        console.warn('Event handler not found:', name);
    }
}

export { evaluate, execute, $dispatch, validateExpression, sanitizeHtml };
