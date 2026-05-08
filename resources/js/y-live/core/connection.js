// Live Connection - 与后端通信
export const LIVE_SAFE_DATA_ATTRS = new Set([
    'data-action',
    'data-live',
    'data-live-id',
    'data-live-state',
    'data-live-fragment',
    'data-live-listeners',
    'data-live-model',
    'data-live-debounce',
    'data-ux-action',
    'data-intl',
    'data-intl-params',
    'data-navigate',
    'data-navigate-fragment',
    'data-navigate-replace',
    'data-navigate-state',
    'data-state',
    'data-component',
    'data-poll',
    'data-stream',
    'data-stream-target',
    'data-live-sse',
]);

if (!window.Y_UI_SAFE_ATTRS) {
    window.Y_UI_SAFE_ATTRS = LIVE_SAFE_DATA_ATTRS;
}

export function getComponentInfo(el) {
    const raw = el.getAttribute('data-live-state') || ''
    if (!raw) {
        return {
            __component: el.dataset.live || '',
            __id: el.dataset.liveId || '',
            __state: '',
            __props: {},
            __actions: [],
        }
    }

    try {
        const parsed = JSON.parse(raw)
        if (parsed && parsed.__component) return parsed
    } catch (e) {}

    return {
        __component: el.dataset.live || '',
        __id: el.dataset.liveId || '',
        __state: raw,
        __props: {},
        __actions: [],
    }
}

export function setLoading(el, loading) {
    const liveEl = el.closest('[data-live]') || el;
    if (loading) liveEl.classList.add('y-loading-root');
    else liveEl.classList.remove('y-loading-root');

    liveEl.querySelectorAll('[data-action], [data-live-action]').forEach(btn => {
        btn.disabled = loading;
        if (loading) btn.classList.add('y-loading');
        else btn.classList.remove('y-loading');
    });
}

function isJsonLiveState(raw) {
    if (!raw) return false
    try {
        const parsed = JSON.parse(raw)
        return !!(parsed && parsed.__component)
    } catch (e) {
        return false
    }
}

export function updateLiveStateAttr(el, newBase64State, patches) {
    const raw = el.getAttribute('data-live-state') || ''

    if (isJsonLiveState(raw)) {
        const info = JSON.parse(raw)
        info.__state = newBase64State
        if (patches) {
            info.__props = { ...info.__props, ...patches }
        }
        el.setAttribute('data-live-state', JSON.stringify(info))
    } else {
        el.setAttribute('data-live-state', newBase64State)
    }
}

function buildActionBody(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    const publicData = state && typeof state.all === 'function' ? state.all() : (state || {});
    const actionParams = collectParams(el, event, extraParams);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const componentsSnapshot = collectAllLiveComponents(el);
    const componentId = el.dataset.liveId || '';
    const parentId = extractParentId(el);

    const info = getComponentInfo(el)

    const body = {
        _component: componentClass,
        _component_id: componentId,
        _action: action,
        _state: info.__state,
        _data: publicData,
        _params: actionParams,
        _components: componentsSnapshot,
    };

    if (parentId) {
        body._parent_id = parentId;
    }

    return {
        headers: {
            'Content-Type': 'application/json',
            'X-Live-Component': componentClass,
            'X-Live-Action': action,
            'X-CSRF-Token': csrfToken,
            'X-Component-Id': componentId,
        },
        body: JSON.stringify(body),
    };
}

function buildStateBody(el, componentClass, stateRef, state) {
    const publicData = state && typeof state.all === 'function' ? state.all() : (state || {});
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const componentId = el.dataset.liveId || '';
    const parentId = extractParentId(el);

    const info = getComponentInfo(el)

    const body = {
        _component: componentClass,
        _component_id: componentId,
        _state: info.__state,
        _data: publicData,
    };

    if (parentId) {
        body._parent_id = parentId;
    }

    return {
        headers: {
            'Content-Type': 'application/json',
            'X-Live-Component': componentClass,
            'X-CSRF-Token': csrfToken,
            'X-Component-Id': componentId,
        },
        body: JSON.stringify(body),
    };
}

/**
 * Extract the parent Live component ID via DOM hierarchy.
 * Walks up from the element to find the closest ancestor [data-live]
 * element, which represents the parent component.
 */
function extractParentId(el) {
    if (!el || !el.parentElement) return null;
    const parentLiveEl = el.parentElement.closest('[data-live]');
    if (parentLiveEl && parentLiveEl !== el) {
        return parentLiveEl.dataset.liveId || null;
    }
    return null;
}

function buildRequestBody(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    return buildActionBody(el, componentClass, action, stateRef, state, event, extraParams);
}

export async function dispatchLive(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    return dispatchAction(el, componentClass, action, stateRef, state, event, extraParams);
}

export async function dispatchAction(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    const { headers, body } = buildActionBody(el, componentClass, action, stateRef, state, event, extraParams);

    setLoading(el, true);

    try {
        const response = await fetch('/live/action', {
            method: 'POST',
            headers,
            body,
        });

        const data = await response.json();
        
        if (!response.ok) {
            return { success: false, error: data.message || data.error || 'Request failed', status: response.status, data };
        }
        
        return { success: true, data };
    } catch (err) {
        console.error('Live action error:', err);
        return { success: false, error: err };
    } finally {
        setLoading(el, false);
    }
}

export async function dispatchState(el, componentClass, stateRef, state) {
    const { headers, body } = buildStateBody(el, componentClass, stateRef, state);

    setLoading(el, true);

    try {
        const response = await fetch('/live/state', {
            method: 'POST',
            headers,
            body,
        });

        const data = await response.json();
        
        if (!response.ok) {
            return { success: false, error: data.message || data.error || 'Request failed', status: response.status, data };
        }
        
        return { success: true, data };
    } catch (err) {
        console.error('Live state update error:', err);
        return { success: false, error: err };
    } finally {
        setLoading(el, false);
    }
}

function buildEventBody(el, componentClass, state, publicData, eventName, eventParams) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const componentId = el.dataset.liveId || '';

    const body = {
        _component: componentClass,
        _component_id: componentId,
        _state: state,
        _data: publicData,
        _event: eventName,
        _params: eventParams,
    };

    return {
        headers: {
            'Content-Type': 'application/json',
            'X-Live-Component': componentClass,
            'X-CSRF-Token': csrfToken,
            'X-Component-Id': componentId,
        },
        body: JSON.stringify(body),
    };
}

export async function dispatchEvent(el, componentClass, state, publicData, eventName, eventParams) {
    const { headers, body } = buildEventBody(el, componentClass, state, publicData, eventName, eventParams);

    setLoading(el, true);

    try {
        const response = await fetch('/live/event', {
            method: 'POST',
            headers,
            body,
        });

        const data = await response.json();

        if (!response.ok) {
            return { success: false, error: data.message || data.error || 'Request failed', status: response.status, data };
        }

        return { success: true, data };
    } catch (err) {
        console.error('Live event dispatch error:', err);
        return { success: false, error: err };
    } finally {
        setLoading(el, false);
    }
}

export function dispatchStream(el, componentClass, action, stateRef, state, event, extraParams = {}, onChunk = null, onDone = null) {
    const { headers, body } = buildActionBody(el, componentClass, action, stateRef, state, event, extraParams);

    setLoading(el, true);

    fetch('/live/stream', { method: 'POST', headers, body })
        .then(async (response) => {
            if (!response.ok) {
                const text = await response.text();
                try {
                    const err = JSON.parse(text);
                    console.error('Stream error:', err.error || text);
                } catch (e) {
                    console.error('Stream error:', text);
                }
                setLoading(el, false);
                if (onDone) onDone();
                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });

                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (!line.trim()) continue;
                    try {
                        const data = JSON.parse(line);
                        if (onChunk) onChunk(data);
                    } catch (e) {}
                }
            }

            if (buffer.trim()) {
                try {
                    const data = JSON.parse(buffer);
                    if (onChunk) onChunk(data);
                } catch (e) {}
            }

            setLoading(el, false);
            if (onDone) onDone();
        })
        .catch((err) => {
            console.error('Stream network error:', err);
            setLoading(el, false);
            if (onDone) onDone();
        });
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
                if (!params[arrKey]) params[arrKey] = [];
                if (!params[arrKey].includes(value)) params[arrKey].push(value);
            } else {
                params[key] = value;
            }
        }
    }

    return params;
}

function collectAllLiveComponents(currentEl = null) {
    const components = [];
    const seenIds = new Set();

    const extractState = (el) => {
        const info = getComponentInfo(el)
        return info ? info.__state : ''
    }

    if (currentEl) {
        let parent = currentEl.parentElement;
        while (parent) {
            const liveParent = parent.closest('[data-live]');
            if (liveParent && liveParent.dataset.liveId && !seenIds.has(liveParent.dataset.liveId)) {
                components.push({
                    id: liveParent.dataset.liveId,
                    class: liveParent.dataset.live,
                    state: extractState(liveParent),
                });
                seenIds.add(liveParent.dataset.liveId);
                parent = liveParent.parentElement;
            } else {
                break;
            }
        }
    }

    document.querySelectorAll('[data-live-listeners]').forEach(el => {
        const id = el.dataset.liveId;
        if (id && !seenIds.has(id)) {
            components.push({
                id: id,
                class: el.dataset.live,
                state: extractState(el),
            });
            seenIds.add(id);
        }
    });

    if (components.length === 0) {
        document.querySelectorAll('[data-live]').forEach(el => {
            const id = el.dataset.liveId;
            if (id && el.dataset.liveState && !seenIds.has(id)) {
                components.push({
                    id: id,
                    class: el.dataset.live,
                    state: extractState(el),
                });
                seenIds.add(id);
            }
        });
    }

    return components;
}
