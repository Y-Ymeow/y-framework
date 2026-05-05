// Directives System - 响应式指令系统
import { ReactiveState, effect, batch } from './reactive.js';

if (!globalThis.__Y_DIRECTIVE_CONTEXT__) {
    globalThis.__Y_DIRECTIVE_CONTEXT__ = {
        exprCache: new Map(),
        execCache: new Map(),
        forbiddenPatterns: [
            /\beval\b/, /\bFunction\b/, /\bwindow\.location\s*=/,
            /\b__proto__\b/, /\bconstructor\b/, /\bprototype\b/
        ]
    };
}

var context = globalThis.__Y_DIRECTIVE_CONTEXT__;

function $dispatch(eventName, detail) {
    const event = new CustomEvent(eventName, { 
        detail: detail || {},
        bubbles: true,
        composed: true
    });
    window.dispatchEvent(event);
}

/**
 * 核心优化：创建一个响应式的作用域代理
 * 它支持向上查找局部变量和全局状态，并确保赋值操作能触发响应式更新
 */
function getFullScope(el, state) {
    const proxy = (state && state.proxy) ? state.proxy : state;
    
    // 收集所有的局部作用域（从当前节点到根节点）
    const localScopes = [];
    let curr = el;
    while (curr) {
        if (curr._y_local_scope) localScopes.push(curr._y_local_scope);
        curr = curr.parentElement;
    }

    return new Proxy({}, {
        get(target, key) {
            // 支持通过 $ 访问作用域本身
            if (key === '$') return this.get(target, '$'); // 递归处理可能有点复杂，直接返回 proxy
            
            // 1. 查找局部作用域
            for (const scope of localScopes) {
                if (key in scope) return scope[key];
            }
            
            // 2. 查找响应式状态
            if (proxy && key in proxy) return proxy[key];
            
            // 3. 回退到 $ 自身引用（用于 $.locale 这种写法）
            if (key === '$') return this; 

            // 4. 回退到全局对象（如 $locale, $dispatch 等）
            if (key in window) return window[key];

            return undefined;
        },
        set(target, key, value) {
            // 1. 尝试写回局部作用域（如果是对象引用则有效）
            for (const scope of localScopes) {
                if (key in scope) {
                    scope[key] = value;
                    return true;
                }
            }
            
            // 2. 写回响应式状态（触发 Proxy set 拦截器）
            if (proxy) {
                proxy[key] = value;
                return true;
            }
            return false;
        },
        has(target, key) {
            if (key === '$') return true;
            for (const scope of localScopes) {
                if (key in scope) return true;
            }
            return proxy ? (key in proxy) : false;
        }
    });
}

export function evaluate(expr, state, el) {
    try {
        if (!context.exprCache.has(expr)) {
            context.exprCache.set(expr, new Function('$', '$dispatch', '$el', '$root', `with($) { return (${expr}) }`));
        }
        
        const scope = getFullScope(el, state);
        
        let rootState = (state && state.proxy) ? state.proxy : state;
        let p = el;
        while(p) { if(p?._y_state) rootState = p._y_state.proxy; p = p?.parentElement; }

        return context.exprCache.get(expr)(scope, $dispatch, el, rootState);
    } catch (e) {
        console.warn(`[y-directive] Evaluate error: "${expr}"`, e);
        return undefined;
    }
}

export function execute(expr, state, event, el) {
    try {
        if (!context.execCache.has(expr)) {
            context.execCache.set(expr, new Function('$', '$event', '$dispatch', '$el', '$root', `with($) { ${expr} }`));
        }
        
        const scope = getFullScope(el, state);
        
        let rootState = (state && state.proxy) ? state.proxy : state;
        let p = el;
        while(p) { if(p?._y_state) rootState = p._y_state.proxy; p = p?.parentElement; }

        context.execCache.get(expr)(scope, event, $dispatch, el, rootState);
    } catch (e) {
        console.warn(`[y-directive] Execute error: "${expr}"`, e);
    }
}

function bindDirective(el, state, type, expr, attrName = null) {
    switch (type) {
        case 'text':
            effect(() => { el.textContent = evaluate(expr, state, el) ?? ''; });
            break;
        case 'html':
            effect(() => { el.innerHTML = evaluate(expr, state, el) ?? ''; });
            break;
        case 'show':
            effect(() => { el.style.display = evaluate(expr, state, el) ? '' : 'none'; });
            break;
        case 'if':
            const placeholder = document.createComment('if');
            effect(() => {
                const visible = !!evaluate(expr, state, el);
                if (visible && !el.parentNode) {
                    placeholder.parentNode?.insertBefore(el, placeholder.nextSibling);
                } else if (!visible && el.parentNode) {
                    el.parentNode.insertBefore(placeholder, el);
                    el.remove();
                }
            });
            break;
        case 'bind':
            const targetAttr = attrName || expr.split(',')[0].trim();
            const bindExpr = attrName ? expr : (expr.split(',')[1]?.trim() || targetAttr);
            
            if (targetAttr === 'class') {
                const staticClass = el.getAttribute('class') || '';
                effect(() => {
                    const val = evaluate(bindExpr, state, el);
                    let dynamicClass = '';
                    if (typeof val === 'object' && val !== null) {
                        dynamicClass = Object.entries(val).filter(([, v]) => v).map(([k]) => k).join(' ');
                    } else {
                        dynamicClass = String(val || '');
                    }
                    el.setAttribute('class', (staticClass + ' ' + dynamicClass).trim());
                });
            } else {
                effect(() => {
                    const val = evaluate(bindExpr, state, el);
                    if (val === false || val === null || val === undefined) el.removeAttribute(targetAttr);
                    else el.setAttribute(targetAttr, val === true ? '' : val);
                });
            }
            break;
        case 'model':
            const isCheckbox = el.type === 'checkbox';
            const isContentEditable = el.contentEditable === 'true' || el.classList.contains('ux-rich-editor__area');
            
            const handleInput = () => {
                const val = isContentEditable ? el.innerHTML : (isCheckbox ? el.checked : el.value);
                // 使用 evaluate/execute 逻辑中的 Proxy 作用域来确保写回正确
                const scope = getFullScope(el, state);
                scope[expr] = val;
            };

            el.addEventListener('input', handleInput);
            if (!isContentEditable && !isCheckbox) {
                el.addEventListener('change', handleInput);
            }

            effect(() => {
                const val = evaluate(expr, state, el);
                if (isContentEditable) {
                    if (el.innerHTML !== val) el.innerHTML = val ?? '';
                } else if (isCheckbox) {
                    el.checked = !!val;
                } else {
                    if (el.value !== val) el.value = val ?? '';
                }
            });
            break;
        case 'for':
            const [itName, listExpr] = expr.split(' in ').map(s => s.trim());
            const template = el.querySelector('template');
            if (!template) return;
            
            el._y_for_nodes = el._y_for_nodes || [];

            effect(() => {
                const list = evaluate(listExpr, state, el) || [];
                
                el._y_for_nodes.forEach(node => node.remove());
                el._y_for_nodes = [];

                list.forEach((item, index) => {
                    const clone = template.content.cloneNode(true);
                    const children = Array.from(clone.children);
                    
                    children.forEach(child => {
                        child._y_local_scope = { [itName]: item, $index: index };
                        el._y_for_nodes.push(child);
                    });
                    
                    el.appendChild(clone);
                    children.forEach(child => initDirectives(child));
                });
            });
            break;
        case 'on':
            const eventName = attrName || expr.split(':')[0].trim();
            const handler = attrName ? expr : expr.split(':')[1].trim();
            const runHandler = (e) => execute(handler, state, e, el);
            
            el.addEventListener(eventName, runHandler);
            if (eventName.includes(':') || eventName.includes('-')) {
                window.addEventListener(eventName, runHandler);
            }
            break;
        case 'effect':
            effect(() => { execute(expr, state, null, el); });
            break;
    }
}

export function initDirectives(root = document) {
    const stateEls = root.querySelectorAll('[data-state]');
    const allStateEls = (root.hasAttribute && root.hasAttribute('data-state')) ? [root, ...stateEls] : stateEls;
    
    allStateEls.forEach(el => {
        if (el._y_state) return;
        try {
            const raw = el.dataset.state || '{}';
            el._y_state = new ReactiveState(JSON.parse(raw));
        } catch (e) { console.error('Invalid data-state:', el); }
    });

    const els = root.querySelectorAll('*');
    const allEls = root.tagName ? [root, ...els] : els;

    allEls.forEach(el => {
        if (el._y_directive_bound) return;
        
        let state = null;
        let current = el;
        while (current) {
            if (current?._y_state) { state = current._y_state; break; }
            current = current?.parentElement;
        }
        if (!state) return;

        let bound = false;
        Array.from(el.attributes).forEach(attr => {
            const name = attr.name;
            const value = attr.value;
            
            if (name === 'data-text') { bindDirective(el, state, 'text', value); bound = true; }
            else if (name === 'data-html') { bindDirective(el, state, 'html', value); bound = true; }
            else if (name === 'data-show') { bindDirective(el, state, 'show', value); bound = true; }
            else if (name === 'data-if') { bindDirective(el, state, 'if', value); bound = true; }
            else if (name === 'data-for') { bindDirective(el, state, 'for', value); bound = true; }
            else if (name === 'data-model' || name === 'data-live-model') { bindDirective(el, state, 'model', value); bound = true; }
            else if (name === 'data-effect') { bindDirective(el, state, 'effect', value); bound = true; }
            else if (name.startsWith('data-on:')) {
                bindDirective(el, state, 'on', value, name.slice(8));
                bound = true;
            }
            else if (name.startsWith('data-bind:')) {
                bindDirective(el, state, 'bind', value, name.slice(10));
                bound = true;
            }
        });

        if (bound) el._y_directive_bound = true;
        
        if (el.hasAttribute('data-cloak')) {
            el.removeAttribute('data-cloak');
        }
    });

    if (root.hasAttribute && root.hasAttribute('data-cloak')) {
        root.removeAttribute('data-cloak');
    }
}
