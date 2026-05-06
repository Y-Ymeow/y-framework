// Live State Management - Live 组件状态管理
import { ReactiveState, effect, batch } from '../../y-directive/reactive';
import { evaluate } from '../../y-directive/evaluator';

export function setupLiveComponent(el, onAction, target = null) {
    if (!el._y_live_ready) {
        el._y_live_ready = true;

        let initialState = {};
        try {
            const stateAttr = el.dataset.state;
            if (stateAttr) {
                initialState = JSON.parse(stateAttr);
            }
        } catch (e) {}

        const state = el._y_state || new ReactiveState(initialState);
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
    const actionEls = scanTarget.querySelectorAll('[data-live-action], [data-action]');
    const list = Array.from(actionEls);
    if (scanTarget.hasAttribute('data-live-action') || scanTarget.hasAttribute('data-action')) list.push(scanTarget);
    
    for (const attr of scanTarget.attributes || []) {
        if (attr.name.startsWith('data-live-action:') || attr.name.startsWith('data-action:')) {
            list.push(scanTarget);
            break;
        }
    }
    
    scanTarget.querySelectorAll('*').forEach(node => {
        for (const attr of node.attributes || []) {
            if (attr.name.startsWith('data-live-action:') || attr.name.startsWith('data-action:')) {
                if (!list.includes(node)) list.push(node);
                break;
            }
        }
    });

    list.forEach(actionEl => {
        if (actionEl._y_action_bound) return;

        const closestLive = actionEl.closest('[data-live]');
        if (closestLive !== el) return;

        actionEl._y_action_bound = true;

        // 优先使用 data-live-action，回退 data-action
        // 支持 data-live-action:click="save" 格式（事件类型嵌入属性名）
        let actionName = null;
        let eventType = 'click';

        for (const attr of actionEl.attributes) {
            const name = attr.name;
            if (name === 'data-live-action' || name === 'data-action') {
                actionName = attr.value;
            } else if (name.startsWith('data-live-action:') || name.startsWith('data-action:')) {
                actionName = attr.value;
                eventType = name.split(':')[1];
            }
        }

        // 兼容旧的 data-action-event
        if (actionEl.dataset.actionEvent && !actionEl.attributes['data-live-action:']) {
            eventType = actionEl.dataset.actionEvent;
        }

        if (!actionName) return;

        actionEl.addEventListener(eventType, (e) => {
            // 需求3: 禁用机制 — data-live-disabled="expr"
            const disabledExpr = actionEl.getAttribute('data-live-disabled');
            if (disabledExpr) {
                const disabled = evaluate(disabledExpr, state, actionEl);
                if (disabled) {
                    e.preventDefault();
                    return;
                }
            }

            // 原生 disabled 属性也阻止触发
            if (actionEl.disabled) {
                e.preventDefault();
                return;
            }

            if (eventType === 'submit' || (eventType === 'click' && (actionEl.tagName === 'A' || actionEl.type === 'submit'))) {
                e.preventDefault();
            }

            let params = {};

            if (actionEl.tagName === 'INPUT' || actionEl.tagName === 'SELECT' || actionEl.tagName === 'TEXTAREA') {
                params.value = actionEl.type === 'checkbox' ? actionEl.checked : actionEl.value;
            }

            // 需求2: 支持表达式参数
            const paramsAttr = actionEl.getAttribute('data-live-action-params') || actionEl.dataset.actionParams;
            if (paramsAttr) {
                // 尝试 JSON 解析
                let parsed = null;
                try {
                    parsed = JSON.parse(paramsAttr);
                } catch (_) {}

                if (parsed !== null) {
                    // 纯 JSON，直接合并
                    params = { ...params, ...parsed };
                } else {
                    // 非 JSON → 当作表达式求值
                    const exprResult = evaluate(paramsAttr, state, actionEl);
                    if (exprResult !== null && exprResult !== undefined && typeof exprResult === 'object') {
                        params = { ...params, ...exprResult };
                    }
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

                if (state && typeof state.set === 'function') {
                    state.set(property, value);
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
