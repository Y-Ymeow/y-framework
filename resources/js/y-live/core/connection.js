// Live Connection - 与后端通信
export const LIVE_SAFE_DATA_ATTRS = new Set([
    'data-action',
    'data-action-event',
    'data-action-params',
    'data-live',
    'data-live-id',
    'data-live-state',
    'data-live-fragment',
    'data-intl',
    'data-navigate',
    'data-navigate-fragment',
    'data-navigate-replace',
    'data-navigate-state',
    'data-state',
    'data-component',
]);

if (!window.Y_UI_SAFE_ATTRS) {
    window.Y_UI_SAFE_ATTRS = LIVE_SAFE_DATA_ATTRS;
}

export async function dispatchLive(el, componentClass, action, stateRef, state, event, extraParams = {}) {
    // 1. 获取当前组件的完整公开属性快照
    const publicData = state && typeof state.all === 'function' ? state.all() : (state || {});
    
    // 2. 收集 Action 触发时的增量参数 (来自 data-model, form, 或 data-action-params)
    const actionParams = collectParams(el, event, extraParams);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const componentsSnapshot = collectAllLiveComponents(el);
    const componentId = el.dataset.liveId || '';

    setLoading(el, true);

    try {
        const response = await fetch('/live/update', {
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
                _state: stateRef.value,      // 加密状态 (Protected/Private)
                _data: publicData,           // 公开状态 (Public)
                _params: actionParams,       // 方法参数
                _components: componentsSnapshot,
            }),
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

function collectParams(el, event, extraParams = {}) {
    const params = { ...extraParams };

    // 1. 自动收集当前组件内所有 data-model 的值
    const liveEl = el.closest('[data-live]') || el;
    liveEl.querySelectorAll('[data-model]').forEach(modelEl => {
        const name = modelEl.dataset.model;
        if (!name) return;
        
        // 避免收集嵌套组件的模型
        if (modelEl.closest('[data-live]') !== liveEl) return;

        let value;
        if (modelEl.type === 'checkbox') value = modelEl.checked;
        else if (modelEl.type === 'radio') {
            if (modelEl.checked) value = modelEl.value;
            else return; // 跳过未选中的 radio
        }
        else value = modelEl.value;

        // 如果是数组名
        if (name.endsWith('[]')) {
            const baseName = name.slice(0, -2);
            if (!params[baseName]) params[baseName] = [];
            params[baseName].push(value);
        } else {
            params[name] = value;
        }
    });

    // 2. 收集表单数据 (如果 Action 是由表单提交或表单内按钮触发)
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
                // 避免重复添加 (如果 data-model 已经收集过)
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
    
    // 1. 收集当前组件的祖先链（这些组件最有可能需要同步状态）
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

    // 2. 收集带有监听器的组件（它们需要响应后端发出的 emit 事件）
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

    // 如果没有 currentEl (比如手动调用)，则退回到收集所有带状态的组件（但建议限制数量）
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

function setLoading(el, loading) {
    const liveEl = el.closest('[data-live]') || el;
    if (loading) liveEl.classList.add('y-loading-root');
    else liveEl.classList.remove('y-loading-root');

    liveEl.querySelectorAll('[data-action]').forEach(btn => {
        btn.disabled = loading;
        if (loading) btn.classList.add('y-loading');
        else btn.classList.remove('y-loading');
    });
}
