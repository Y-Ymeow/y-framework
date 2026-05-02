// Live State Management - Live 组件状态管理
import { ReactiveState, effect, batch } from '../../y-directive/reactive.js';

export function setupLiveComponent(el, onAction, target = null) {
    if (!el._y_live_ready) {
        el._y_live_ready = true;

        const state = el._y_state || new ReactiveState({});
        if (!el._y_state) el._y_state = state;

        el._y_live_component_class = el.dataset.live || '';
        el._y_live_state_ref = { value: el.dataset.liveState || '' };

        el._liveDispatch = (action, e, params = {}) => {
            if (onAction) {
                onAction(el, el._y_live_component_class, action, el._y_live_state_ref, el._y_state, e, params);
            }
        };
    }

    // 每次 setup 都要尝试绑定，因为可能有新的 DOM 节点 (target)
    const scanTarget = target || el;
    const componentClass = el._y_live_component_class;
    const stateRef = el._y_live_state_ref;
    const state = el._y_state;

    bindLiveActions(el, componentClass, stateRef, state, onAction, scanTarget);
    bindLiveModels(el, componentClass, stateRef, state, onAction, scanTarget);
}

function bindLiveActions(el, componentClass, stateRef, state, onAction, scanTarget) {
    // 如果 scanTarget 本身就有 data-action，也需要处理
    const actionEls = scanTarget.querySelectorAll('[data-action]');
    const list = Array.from(actionEls);
    if (scanTarget.hasAttribute('data-action')) list.push(scanTarget);

    list.forEach(actionEl => {
        if (actionEl._y_action_bound) return;

        const closestLive = actionEl.closest('[data-live]');
        if (closestLive !== el) return;

        actionEl._y_action_bound = true;

        const actionName = actionEl.dataset.action;
        const eventType = actionEl.dataset.actionEvent || 'click';

        actionEl.addEventListener(eventType, (e) => {
            if (eventType === 'submit' || (eventType === 'click' && (actionEl.tagName === 'A' || actionEl.type === 'submit'))) {
                e.preventDefault();
            }

            let params = {};

            if (actionEl.tagName === 'INPUT' || actionEl.tagName === 'SELECT' || actionEl.tagName === 'TEXTAREA') {
                params.value = actionEl.type === 'checkbox' ? actionEl.checked : actionEl.value;
            }

            const paramsAttr = actionEl.dataset.actionParams;
            if (paramsAttr) {
                try {
                    const parsed = JSON.parse(paramsAttr);
                    params = { ...params, ...parsed };
                } catch (err) {
                    console.error('Failed to parse data-action-params:', paramsAttr, err);
                }
            }

            for (const key in params) {
                if (params[key] === '__value__') {
                    params[key] = (actionEl.type === 'checkbox' ? actionEl.checked : actionEl.value);
                }
            }

            const dispatcher = onAction || el._liveDispatch;
            if (typeof dispatcher === 'function') {
                const isStream = actionEl.hasAttribute('data-stream');
                dispatcher(el, componentClass, actionName, stateRef, state, e, params, isStream);
            }
        });
    });
}

function bindLiveModels(el, componentClass, stateRef, state, onAction, scanTarget) {
    const modelEls = scanTarget.querySelectorAll('[data-live-model]');
    const list = Array.from(modelEls);
    if (scanTarget.hasAttribute('data-live-model')) list.push(scanTarget);

    list.forEach(modelEl => {
        if (modelEl._y_live_model_bound) return;
        
        const closestLive = modelEl.closest('[data-live]');
        if (closestLive !== el) return;
        
        modelEl._y_live_model_bound = true;

        const property = modelEl.dataset.liveModel;
        const tagName = modelEl.tagName;
        const type = modelEl.type;
        
        const isText = (tagName === 'INPUT' && (type === 'text' || type === 'email' || type === 'password' || type === 'number')) 
                    || tagName === 'TEXTAREA'
                    || modelEl.contentEditable === 'true';
        
        const eventType = modelEl.dataset.liveEvent || (isText ? 'input' : 'change');
        const debounceMs = parseInt(modelEl.dataset.liveDebounce || '300');

        let debounceTimer = null;
        modelEl.addEventListener(eventType, (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                let value;
                if (modelEl.contentEditable === 'true') {
                    value = modelEl.innerHTML;
                } else if (type === 'checkbox') {
                    value = modelEl.checked;
                } else if (type === 'radio') {
                    if (modelEl.checked) value = modelEl.value;
                    else return;
                } else {
                    value = modelEl.value;
                }

                const dispatcher = onAction || el._liveDispatch;
                if (typeof dispatcher === 'function') {
                    dispatcher(el, componentClass, '__updateProperty', stateRef, state, e, {
                        property: property,
                        value: value
                    });
                }
            }, debounceMs);
        });
    });
}

export function applyLiveResponse(el, data, state, stateRef) {
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

    return data.operations || [];
}
