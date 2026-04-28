// Y-Live - Live 框架入口
import { initDirectives } from '../y-directive/directives.js';
import { setupLiveComponent, applyLiveResponse } from './core/state.js';
import { dispatchLive } from './core/connection.js';
import { applyLiveFragment, createSafeFragment, replaceLiveHtml } from './core/dom.js';
import { bindNavigateLinks, navigate } from './navigate.js';
import { executeOperation, executeOperations } from './operations.js';
import { initBadge } from './badge.js';
import { initIntl, switchLocale, getLocale, getTranslation } from './intl.js';

let config = {
    badge: false,
    navigate: true,
};

export function configure(options) {
    config = { ...config, ...options };
}

function rebindActions(target, liveState = null) {
    // 1. 初始化新 DOM 中的指令 (data-text, data-on, data-bind)
    initDirectives(target);

    // 2. 初始化 Live Action (data-action)
    // 检查 target 是否是 live 组件或其内部的一部分
    const liveEl = target.closest('[data-live]');
    if (liveEl) {
        setupLiveComponent(liveEl, dispatchAction, target);
    }
    
    // 处理嵌套的 live 组件
    target.querySelectorAll('[data-live]').forEach(el => {
        setupLiveComponent(el, dispatchAction);
    });
}

async function dispatchAction(el, componentClass, action, stateRef, state, event, params = {}) {
    const result = await dispatchLive(el, componentClass, action, stateRef, state, event, params);
    
    if (!result.success) {
        console.error('Live error:', result.error);
        return;
    }

    const data = result.data;
    // 更新状态代理，这会触发指令系统的 effect (data-bind, data-text)
    applyLiveResponse(el, data, state, stateRef);

    // 处理 DOM 更新
    if (data.domPatches && data.domPatches.length > 0) {
        data.domPatches.forEach(patch => {
            const target = document.querySelector(patch.selector);
            if (target) {
                replaceLiveHtml(target, patch.html, data.state);
                rebindActions(target, data.state);
            }
        });
    }

    if (data.fragments && data.fragments.length > 0) {
        data.fragments.forEach(fragment => {
            const liveEl = findLiveElement(el, fragment);
            if (liveEl) {
                applyLiveFragment(liveEl, fragment, data.state);
                rebindActions(liveEl, data.state);
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
                targetEl._y_state.merge(update.patches);
            }

            if (update.fragments && update.fragments.length > 0) {
                update.fragments.forEach(fragment => {
                    applyLiveFragment(targetEl, fragment, update.state);
                    rebindActions(targetEl, update.state);
                });
            }
        });
    }

    if (data.operations) {
        data.operations.forEach(op => executeOperation(op));
    }

    window.dispatchEvent(new CustomEvent('y:updated', { detail: { el, data } }));
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

function boot(root = document) {
    // 首先初始化指令
    initDirectives(root);

    // 然后初始化 Live 组件
    root.querySelectorAll('[data-live]').forEach(el => {
        setupLiveComponent(el, dispatchAction);
    });

    if (config.navigate) {
        bindNavigateLinks(root);
    }

    if (config.badge) {
        initBadge();
    }

    // 初始化 Intl
    initIntl();
}

const L = {
    boot,
    configure,
    navigate,
    dispatch: dispatchAction,
    executeOperation,
    executeOperations,
    setupLiveComponent,
    applyLiveResponse,
    applyLiveFragment,
    // Intl
    switchLocale,
    getLocale,
    getTranslation,
};

window.L = L;

function doBoot() {
    L.boot();
    window.dispatchEvent(new CustomEvent('l:ready', { detail: L }));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', doBoot);
} else {
    doBoot();
}

window.addEventListener('popstate', (e) => {
    if (e.state && e.state.navigateUrl) {
        navigate(e.state.navigateUrl);
    }
});

export default L;
