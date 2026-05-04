// Y-Live - Live 框架入口
import { initDirectives } from '../y-directive/directives.js';
import { setupLiveComponent, applyLiveResponse } from './core/state.js';
import { dispatchLive, dispatchStream, setLoading } from './core/connection.js';
import { applyLiveFragment, createSafeFragment, replaceLiveHtml } from './core/dom.js';
import { bindNavigateLinks, navigate } from './navigate.js';
import { executeOperation, executeOperations } from './operations.js';
import { initBadge } from './badge.js';
import { initIntl, switchLocale, getLocale, getTranslation } from './intl.js';
import { initPersistent } from './persistent.js';
import Poll from './poll.js';
import './sse-live.js';

let config = {
    badge: false,
    navigate: true,
};

export function configure(options) {
    config = { ...config, ...options };
}

function rebindActions(target, liveState = null) {
    initDirectives(target);

    const liveEl = target.closest('[data-live]');
    if (liveEl) {
        setupLiveComponent(liveEl, dispatchAction, target);
    }

    target.querySelectorAll('[data-live]').forEach(el => {
        setupLiveComponent(el, dispatchAction);
    });
}

async function dispatchAction(el, componentClass, action, stateRef, state, event, params = {}, isStream = false) {
    if (isStream) {
        dispatchStream(
            el, componentClass, action, stateRef, state, event, params,
            (chunk) => processStreamChunk(chunk, el, state, stateRef),
            () => {
                setLoading(el, false);
                el.dispatchEvent(new CustomEvent('live:stream-done', { detail: { el } }));
            }
        );
        return;
    }

    const result = await dispatchLive(el, componentClass, action, stateRef, state, event, params);

    if (!result.success) {
        setLoading(el, false);
        console.error('Live error:', result.error);
        return;
    }

    const data = result.data;
    applyLiveResponse(el, data, state, stateRef);

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

                // 触发持久化保存事件
                window.dispatchEvent(new CustomEvent('y:component-updated', {
                    detail: {
                        componentId: update.componentId,
                        patches: update.patches,
                    }
                }));
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
        data.operations.forEach(op => L.executeOperation(op));
    }

    window.dispatchEvent(new CustomEvent('y:updated', { detail: { el, data } }));
}

function processStreamChunk(data, el, state, stateRef) {
    if (data.type === 'live') {
        applyLiveResponse(el, data, state, stateRef);

        if (data.operations) {
            data.operations.forEach(op => L.executeOperation(op));
        }
        return;
    }

    if (data.type === 'text') {
        const target = findStreamTarget(el, data.target);
        if (target) {
            target.textContent += data.content || '';
        }
        el.dispatchEvent(new CustomEvent('live:stream', { detail: data }));
        return;
    }

    if (data.type === 'thinking') {
        const target = findStreamTarget(el, data.target);
        if (target) {
            target.setAttribute('data-stream-thinking', data.content || '');
            target.textContent = data.content || '';
        }
        el.dispatchEvent(new CustomEvent('live:stream', { detail: data }));
        return;
    }

    if (data.type === 'progress') {
        el.dispatchEvent(new CustomEvent('live:stream', { detail: data }));
        return;
    }

    if (data.type === 'done') {
        if (data.state || data.patches) {
            applyLiveResponse(el, data, state, stateRef);
        }
        if (data.operations) {
            data.operations.forEach(op => L.executeOperation(op));
        }
        el.dispatchEvent(new CustomEvent('live:stream', { detail: data }));
        return;
    }

    el.dispatchEvent(new CustomEvent('live:stream', { detail: data }));
}

function findStreamTarget(el, targetName) {
    if (targetName) {
        return el.querySelector(`[data-stream-target="${targetName}"]`);
    }
    return el.querySelector('[data-stream-target]') || null;
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
    initDirectives(root);

    root.querySelectorAll('[data-live]').forEach(el => {
        setupLiveComponent(el, dispatchAction);
    });

    if (config.navigate) {
        bindNavigateLinks(root);
    }

    if (config.badge) {
        initBadge();
    }

    initIntl();
    initPersistent();

    Poll.autoInit(root);
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
    switchLocale,
    getLocale,
    getTranslation,
    Poll,
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
