// Live Connection - 与后端通信
export const LIVE_SAFE_DATA_ATTRS = new Set([
    'data-action',
    'data-action-event',
    'data-action-params',
    'data-live',
    'data-live-id',
    'data-live-state',
    'data-live-fragment',
    'data-live-model',
    'data-live-debounce',
    'data-live-event',
    'data-ux-model',
    'data-ux-action',
    'data-intl',
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

export function setLoading(el, loading) {
    const liveEl = el.closest('[data-live]') || el;
    if (loading) liveEl.classList.add('y-loading-root');
    else liveEl.classList.remove('y-loading-root');

    liveEl.querySelectorAll('[data-action]').forEach(btn => {
        btn.disabled = loading;
        if (loading) btn.classList.add('y-loading');
        else btn.classList.remove('y-loading');
    });
}

function buildRequestBody(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    const publicData = state && typeof state.all === 'function' ? state.all() : (state || {});
    const actionParams = collectParams(el, event, extraParams);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const componentsSnapshot = collectAllLiveComponents(el);
    const componentId = el.dataset.liveId || '';

    return {
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
            _data: publicData,
            _params: actionParams,
            _components: componentsSnapshot,
        }),
    };
}

export async function dispatchLive(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    const { headers, body } = buildRequestBody(el, componentClass, action, stateRef, state, event, extraParams);

    setLoading(el, true);

    try {
        const response = await fetch('/live/update', {
            method: 'POST',
            headers,
            body,
        });

        const data = await response.json();
        return { success: true, data };
    } catch (err) {
        console.error('Live network error:', err);
        return { success: false, error: err };
    } finally {
        setLoading(el, false);
    }
}

export function dispatchStream(el, componentClass, action, stateRef, state, event, extraParams = {}, onChunk = null, onDone = null) {
    const { headers, body } = buildRequestBody(el, componentClass, action, stateRef, state, event, extraParams);

    setLoading(el, true);

    fetch('/live/stream', { method: 'POST', headers, body })
        .then(async (response) => {
            console.log('Stream response:', response);
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

    const liveEl = el.closest('[data-live]') || el;
    liveEl.querySelectorAll('[data-model]').forEach(modelEl => {
        const name = modelEl.dataset.model;
        if (!name) return;

        if (modelEl.closest('[data-live]') !== liveEl) return;

        let value;
        if (modelEl.type === 'checkbox') value = modelEl.checked;
        else if (modelEl.type === 'radio') {
            if (modelEl.checked) value = modelEl.value;
            else return;
        }
        else value = modelEl.value;

        if (name.endsWith('[]')) {
            const baseName = name.slice(0, -2);
            if (!params[baseName]) params[baseName] = [];
            params[baseName].push(value);
        } else {
            params[name] = value;
        }
    });

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

    if (currentEl) {
        let parent = currentEl.parentElement;
        while (parent) {
            const liveParent = parent.closest('[data-live]');
            if (liveParent && liveParent.dataset.liveId && !seenIds.has(liveParent.dataset.liveId)) {
                components.push({
                    id: liveParent.dataset.liveId,
                    class: liveParent.dataset.live,
                    state: liveParent.dataset.liveState || '',
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
                state: el.dataset.liveState || '',
            });
            seenIds.add(id);
        }
    });

    if (!currentEl && components.length === 0) {
        document.querySelectorAll('[data-live]').forEach(el => {
            const id = el.dataset.liveId;
            if (id && el.dataset.liveState && !seenIds.has(id)) {
                components.push({
                    id: id,
                    class: el.dataset.live,
                    state: el.dataset.liveState || '',
                });
                seenIds.add(id);
            }
        });
    }

    return components;
}
