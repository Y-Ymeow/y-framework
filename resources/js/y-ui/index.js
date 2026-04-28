import { ReactiveState, effect, batch } from './reactive.js';
import { initDirectives, evaluate } from './directives.js';

const registry = new Map();
const LIVE_SAFE_DATA_ATTRS = new Set([
    'data-action',
    'data-action-event',
    'data-action-params',
    'data-live',
    'data-live-id',
    'data-live-state',
    'data-live-fragment',
    'data-navigate',
    'data-navigate-fragment',
    'data-navigate-replace',
    'data-navigate-state',
    'data-state',
    'data-component',
]);

window.Y_UI_SAFE_ATTRS = LIVE_SAFE_DATA_ATTRS;
const LIVE_BLOCKED_TAGS = new Set([
    'script',
    'iframe',
    'object',
    'embed',
    'link',
    'meta',
    'base',
]);

function boot(root = document) {
    initDirectives(root);

    root.querySelectorAll('[data-live]').forEach(el => {
        if (el._y_live_ready) return;
        setupLiveComponent(el);
    });

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

    bindNavigateLinks(root);
}

function setupLiveComponent(el) {
    el._y_live_ready = true;

    const state = el._y_state || new ReactiveState({});
    if (!el._y_state) el._y_state = state;

    const componentClass = el.dataset.live || '';
    const stateRef = { value: el.dataset.liveState || '' };
    el._y_live_state_ref = stateRef;

    bindLiveActions(el, componentClass, stateRef, state);

    el._liveDispatch = (action, e) => {
        Y.dispatchLive(el, componentClass, action, stateRef, state, e);
    };
}

function bindLiveActions(el, componentClass, stateRef, state) {
    el.querySelectorAll('[data-action]').forEach(actionEl => {
        if (actionEl._y_action_bound) return;

        const closestLive = actionEl.closest('[data-live]');
        if (closestLive !== el) return;

        actionEl._y_action_bound = true;

        const actionName = actionEl.dataset.action;
        const eventType = actionEl.dataset.actionEvent || 'click';

        actionEl.addEventListener(eventType, (e) => {
            if (eventType === 'submit' || eventType === 'click') {
                if (actionEl.type === 'submit' || actionEl.getAttribute('form') || actionEl.tagName === 'A') {
                    e.preventDefault();
                }
            }

            let params = {};

            // Auto-inject value from input elements
            if (actionEl.tagName === 'INPUT' || actionEl.tagName === 'SELECT' || actionEl.tagName === 'TEXTAREA') {
                const val = actionEl.type === 'checkbox' ? actionEl.checked : actionEl.value;
                params.value = val;
            }

            const paramsAttr = actionEl.dataset.actionParams;
            if (paramsAttr) {
                try {
                    const parsed = JSON.parse(paramsAttr);
                    // Merge parameters instead of overwriting
                    params = { ...params, ...parsed };
                } catch (e) {
                    console.error('Failed to parse data-action-params:', paramsAttr, e);
                }
            }

            // Handle __value__ placeholder (commonly used in select change events)
            for (const key in params) {
                if (params[key] === '__value__') {
                    params[key] = (actionEl.type === 'checkbox' ? actionEl.checked : actionEl.value);
                }
            }

            // Security check: Warn instead of block for unsigned params in development/demo
            if (Object.keys(params).length > 0 && !isSignedActionParams(params)) {
                // We allow unsigned params because many components like DataTable/Pagination
                // need dynamic client-side parameter injection.
                if (window.Y_DEBUG) {
                    console.debug('Unsigned data-action-params sent:', actionName, params);
                }
            }

            Y.dispatchLive(el, componentClass, actionName, stateRef, state, e, params);
        });
    });
}

async function dispatchLive(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    const params = collectParams(el, event, extraParams);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const componentsSnapshot = collectAllLiveComponents();
    const componentId = el.dataset.liveId || '';

    setLoading(el, true);

    try {
        const response = await fetch('/live', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Live-Component': componentClass,
                'X-Live-Action': action,
                'X-CSRF-Token': csrfToken,
                'X-Component-Id': componentId,
            },
            body: JSON.stringify({
                _component: componentClass,
                _component_id: componentId,
                _action: action,
                _state: stateRef.value,
                _params: params,
                _components: componentsSnapshot,
            }),
        });

        const data = await response.json();
        if (!data.success) {
            console.error('Live error:', data.error);
            return;
        }

        Y.applyLiveResponse(el, data, state, stateRef, componentId);
    } catch (err) {
        console.error('Live network error:', err);
    } finally {
        setLoading(el, false);
    }
}

function collectParams(el, event, extraParams = {}) {
    const params = { ...extraParams };

    let form = null;
    if (event && event.target) {
        if (event.type === 'submit' && event.target.tagName === 'FORM') {
            form = event.target;
        } else if (event.type === 'click') {
            const btn = event.target.closest('button[type="submit"], input[type="submit"]');
            if (btn) {
                const formId = btn.getAttribute('form');
                form = formId ? document.getElementById(formId) : btn.closest('form');
            }
        }
    }

    if (form) {
        const fd = new FormData(form);
        for (const [key, value] of fd.entries()) {
            if (key.endsWith('[]')) {
                const arrKey = key.slice(0, -2);
                (params[arrKey] = params[arrKey] || []).push(value);
            } else {
                params[key] = value;
            }
        }
    }

    return params;
}

function applyLiveResponse(el, data, state, stateRef, componentId) {
    if (data.state) {
        el.setAttribute('data-live-state', data.state);
        if (stateRef) stateRef.value = data.state;
    }

    if (data.patches) {
        el.setAttribute('data-state', JSON.stringify(data.patches));
        batch(() => {
            state.merge(data.patches);
        });
    }

    if (data.domPatches && data.domPatches.length > 0) {
        data.domPatches.forEach(patch => {
            const target = document.querySelector(patch.selector);
            if (target) {
                replaceLiveHtml(target, patch.html, data.state);
            }
        });
    }

    if (data.fragments && data.fragments.length > 0) {
        data.fragments.forEach(fragment => {
            const liveEl = findLiveElement(el, fragment);
            if (liveEl) {
                applyLiveFragment(liveEl, fragment, data.state);
            }
        });
    }

    if (data.componentUpdates && data.componentUpdates.length > 0) {
        data.componentUpdates.forEach(update => {
            const targetEl = document.querySelector(`[data-live-id="${update.componentId}"]`);
            if (!targetEl) return;

            if (update.state) {
                targetEl.setAttribute('data-live-state', update.state);
                if (targetEl._y_live_state_ref) {
                    targetEl._y_live_state_ref.value = update.state;
                }
            }

            if (update.patches && targetEl._y_state) {
                targetEl.setAttribute('data-state', JSON.stringify(update.patches));
                batch(() => {
                    targetEl._y_state.merge(update.patches);
                });
            }

            if (update.fragments && update.fragments.length > 0) {
                update.fragments.forEach(fragment => {
                    applyLiveFragment(targetEl, fragment, update.state);
                });
            }
        });
    }

    if (data.operations) {
        data.operations.forEach(op => Y.executeOperation(op));
    }

    window.dispatchEvent(new CustomEvent('y:updated', { detail: { el, data } }));
}

function setLoading(el, loading) {
    if (loading) el.classList.add('y-loading-root');
    else el.classList.remove('y-loading-root');

    el.querySelectorAll('[data-action]').forEach(btn => {
        btn.disabled = loading;
        if (loading) btn.classList.add('y-loading');
        else btn.classList.remove('y-loading');
    });
}

function findLiveElement(el, fragment) {
    if (el.hasAttribute('data-live')) return el;
    
    const parent = el.closest('[data-live]');
    if (parent) return parent;
    
    if (fragment.componentId) {
        return document.querySelector(`[data-live-id="${fragment.componentId}"]`);
    }
    
    return null;
}

function collectAllLiveComponents() {
    const components = [];
    document.querySelectorAll('[data-live]').forEach(el => {
        if (el.dataset.liveState && el.dataset.live) {
            components.push({
                id: el.dataset.liveId || '',
                class: el.dataset.live,
                state: el.dataset.liveState || '',
            });
        }
    });
    return components;
}

const executeOperation = (op) => {
    switch (op.op) {
        case 'update': {
            const val = String(op.value ?? '');
            let input = document.querySelector(`input[name="${op.target}"], textarea[name="${op.target}"], select[name="${op.target}"]`)
                     || document.getElementById(op.target);
            if (input) { input.value = val; input.dispatchEvent(new Event('change', { bubbles: true })); }
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
            if (el) {
                el.appendChild(createSafeFragment(op.html));
                const liveEl = el.closest('[data-live]');
                if (liveEl && liveEl._y_state) {
                    bindLiveActions(liveEl, liveEl.dataset.live || '', { value: liveEl.dataset.liveState || '' }, liveEl._y_state);
                }
            }
            break;
        }
        case 'remove': { const el = document.querySelector(op.selector); if (el) el.remove(); break; }
        case 'addClass': { const el = document.querySelector(op.selector); if (el) el.classList.add(...op.class.split(' ')); break; }
        case 'removeClass': { const el = document.querySelector(op.selector); if (el) el.classList.remove(...op.class.split(' ')); break; }
        case 'openModal': {
            const m = document.querySelector(`[data-ux-modal="${op.id}"]`);
            if (m) { m.setAttribute('data-visible', ''); document.body.style.overflow = 'hidden'; }
            break;
        }
        case 'closeModal': {
            const m = document.querySelector(`[data-ux-modal="${op.id}"]`);
            if (m) { m.removeAttribute('data-visible'); document.body.style.overflow = ''; }
            break;
        }
        case 'redirect':
            if (document.startViewTransition) document.startViewTransition(() => { window.location.href = op.url; });
            else window.location.href = op.url;
            break;
        case 'reload': window.location.reload(); break;
        case 'js':
            console.warn('Live js operation is disabled for security reasons.');
            break;
        case 'dispatch':
            window.dispatchEvent(new CustomEvent(op.event, { detail: op.detail || {} }));
            break;
    }
};

const replaceLiveHtml = (target, html, liveState = null) => {
    target.replaceChildren(createSafeFragment(html));

    const liveEl = target.closest('[data-live]');
    if (!liveEl || !liveEl._y_state) return;

    bindLiveActions(
        liveEl,
        liveEl.dataset.live || '',
        { value: liveState ?? liveEl.dataset.liveState ?? '' },
        liveEl._y_state
    );
};

const rebindActions = (liveEl, liveState = null) => {
    if (!liveEl || !liveEl._y_state) return;
    bindLiveActions(
        liveEl,
        liveEl.dataset.live || '',
        { value: liveState ?? liveEl.dataset.liveState ?? '' },
        liveEl._y_state
    );
};

const applyLiveFragment = (liveEl, fragment, liveState = null) => {
    if (!liveEl || !fragment?.name) return;

    const escapedName = CSS.escape(fragment.name);
    const selector = `[data-live-fragment="${escapedName}"]`;
    
    // 检查标记是否在自身，或者在子元素中
    const target = liveEl.matches(selector) ? liveEl : liveEl.querySelector(selector);
    
    if (!target) return;

    const mode = fragment.mode || 'replace';

    if (mode === 'append') {
        target.appendChild(createSafeFragment(fragment.html || ''));
        rebindActions(liveEl, liveState);
        return;
    }

    if (mode === 'prepend') {
        target.insertBefore(createSafeFragment(fragment.html || ''), target.firstChild);
        rebindActions(liveEl, liveState);
        return;
    }

    replaceLiveHtml(target, fragment.html || '', liveState);
};

const createSafeFragment = (html) => {
    const template = document.createElement('template');
    template.innerHTML = html;
    sanitizeLiveTree(template.content);
    return template.content;
};

const isSignedActionParams = (params) => {
    if (!params || typeof params !== 'object' || Array.isArray(params)) return false;
    return typeof params._signature === 'string' && params._signature !== '' && typeof params._payload === 'object' && params._payload !== null && !Array.isArray(params._payload);
};

const sanitizeLiveTree = (root) => {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
    const nodes = [];

    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }

    nodes.forEach((node) => {
        const tagName = node.tagName.toLowerCase();
        if (LIVE_BLOCKED_TAGS.has(tagName)) {
            node.remove();
            return;
        }

        Array.from(node.attributes).forEach((attr) => {
            const name = attr.name.toLowerCase();
            const value = attr.value ?? '';

            if (name.startsWith('on')) {
                node.removeAttribute(attr.name);
                return;
            }

            if (name.startsWith('data-') && !LIVE_SAFE_DATA_ATTRS.has(name)) {
                const globalSet = window.Y_UI_SAFE_ATTRS;
                if (!globalSet || !globalSet.has(name)) {
                    node.removeAttribute(attr.name);
                    return;
                }
            }

            if ((name === 'href' || name === 'src' || name === 'action' || name === 'formaction') && isUnsafeUrl(value)) {
                node.removeAttribute(attr.name);
            }
        });
    });
};

const isUnsafeUrl = (value) => /^\s*javascript:/i.test(value);

// ===== Navigate / data-navigate =====
let progressBarEl = null;

function ensureProgressBar() {
    if (progressBarEl) return;

    const style = document.createElement('style');
    style.textContent = `
        .y-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            z-index: 9999;
            transition: width 0.2s ease-out, opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }
        .y-progress-bar.active {
            opacity: 1;
        }
        .y-progress-bar.indeterminate {
            width: 30%;
            animation: y-progress-pulse 1s infinite;
        }
        @keyframes y-progress-pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    progressBarEl = document.createElement('div');
    progressBarEl.className = 'y-progress-bar';
    document.body.appendChild(progressBarEl);
}

function showProgress() {
    ensureProgressBar();
    progressBarEl.classList.add('active', 'indeterminate');
}

function setProgress(percent) {
    if (!progressBarEl) return;
    progressBarEl.classList.remove('indeterminate');
    progressBarEl.style.width = percent + '%';
}

function hideProgress() {
    if (!progressBarEl) return;
    setProgress(100);
    setTimeout(() => {
        progressBarEl.classList.remove('active');
        progressBarEl.style.width = '0%';
    }, 200);
}

function bindNavigateLinks(root) {
    root.querySelectorAll('a[data-navigate]').forEach(link => {
        if (link._y_navigate_bound) return;
        link._y_navigate_bound = true;

        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;

            e.preventDefault();

            const options = {};
            if (link.hasAttribute('data-navigate-replace')) {
                options.replace = true;
            }
            if (link.dataset.navigateFragment) {
                options.fragment = link.dataset.navigateFragment;
            }
            if (link.dataset.navigateState) {
                try { options.state = JSON.parse(link.dataset.navigateState); } catch (e) {}
            }

            navigate(href, options);
        });
    });
}

async function navigate(url, options = {}) {
    const { replace = false, fragment = null, state = null } = options;

    showProgress();

    try {
        const response = await fetch('/navigate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Navigate': 'true',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ url, fragment, state }),
        });

        if (!response.ok) {
            await logResponseDetails(response);
            throw new Error('Navigate failed: ' + response.status);
        }

        const data = await response.json();

        if (!data.fragments || !Array.isArray(data.fragments)) {
            throw new Error('Invalid navigate response: ' + JSON.stringify(data));
        }

        console.log('Navigate response:', data);

        const updateDom = () => {
            if (data.title) {
                document.title = data.title;
            }

            if (replace) {
                window.history.replaceState({ navigateUrl: url }, data.title || '', url);
            } else {
                window.history.pushState({ navigateUrl: url }, data.title || '', url);
            }

            data.fragments.forEach(fragment => {
                if (!fragment.name) return;
                let target;
                if (fragment.name === 'body') {
                    target = document.body;
                } else {
                    target = document.querySelector(`[data-navigate-fragment="${fragment.name}"]`);
                }
                if (target) {
                    replaceNavigateHtml(target, fragment.html);
                }
            });

            if (data.scripts && Array.isArray(data.scripts)) {
                data.scripts.forEach(script => {
                    try {
                        eval(script);
                    } catch (err) {
                        console.error('Navigate script error:', err);
                    }
                });
            }

            data.fragments.forEach(fragment => {
                if (!fragment.name) return;
                let target;
                if (fragment.name === 'body') {
                    target = document.body;
                } else {
                    target = document.querySelector(`[data-navigate-fragment="${fragment.name}"]`);
                }
                if (target) {
                    bindNavigateLinks(target);
                }
            });
        };

        if (document.startViewTransition) {
            document.startViewTransition(updateDom);
        } else {
            updateDom();
        }

        hideProgress();

    } catch (err) {
        console.error('Navigate error:', err);
        hideProgress();
        window.location.href = url;
    }
}

// 辅助函数：显示网络响应详情
async function logResponseDetails(response) {
    console.log('Response status:', response.status);
    console.log('Response headers:', [...response.headers.entries()]);
    try {
        const text = await response.clone().text();
        console.log('Response body preview:', text.substring(0, 500));
    } catch (e) {
        console.log('Could not read response body:', e);
    }
}

function replaceNavigateHtml(target, html) {
    // 检查目标本身是否是 Live 组件
    const isLiveTarget = target.hasAttribute('data-live');
    const liveState = isLiveTarget ? target._y_state : null;

    target.replaceChildren(createSafeFragment(html));

    // 如果目标是 Live 组件，重新初始化它
    if (isLiveTarget && target.dataset.live) {
        setupLiveComponent(target);
    }

    // 重新绑定新内容中的 Live 组件
    target.querySelectorAll('[data-live]').forEach(el => {
        if (!el._y_live_ready) {
            setupLiveComponent(el);
        }
    });

    // 重新绑定新内容中的导航链接
    bindNavigateLinks(target);
}

function register(name, definition) {
    registry.set(name, definition);
}

const Y = {
    boot,
    register,
    executeOperation,
    dispatchLive,
    applyLiveResponse,
    navigate,
};

window.Y = Y;

// 统一引导逻辑
const doBoot = () => {
    Y.boot();
    window.dispatchEvent(new CustomEvent('y:ready', { detail: Y }));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', doBoot);
} else {
    doBoot();
}

// 监听浏览器前进/后退
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.navigateUrl) {
        navigate(e.state.navigateUrl);
    }
});

export default Y;
