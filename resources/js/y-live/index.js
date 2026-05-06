import { directive, initDirectives } from '../y-directive/index.js';
import { setupLiveComponent, applyLiveResponse } from './core/state.js';
import { dispatchLive, dispatchStream, setLoading } from './core/connection.js';
import { replaceLiveHtml, applyLiveFragment } from './core/dom.js';
import { executeOperation } from './operations.js';
import { initIntl } from './intl.js';
import { bindNavigateLinks, navigate } from './navigate.js';
import Poll from './poll.js';

// 1. 注册 data-live 指令
directive('live', (el, state, method, { content }) => {
    // 这里的 method 通常是组件类名，content 也是
    const componentClass = method || content;
    
    // 初始化 Live 组件容器
    setupLiveComponent(el, (el, componentClass, action, stateRef, state, event, params, isStream) => {
        return dispatchAction(el, componentClass, action, stateRef, state, event, params, isStream);
    });
    
    // 标记已由指令系统接管
    el._y_live_managed = true;
});

// 2. 注册 data-action 指令 (支持 data-action:click="save" 或 data-action:click="save({id: 1})")
directive('action', (el, state, method, { content, modifiers, $execute, execute }) => {
    // 如果 method 为空（即 data-action="save"），默认使用 click
    const eventType = method || 'click';
    
    const handler = async (e) => {
        const liveEl = el.closest('[data-live]');
        if (!liveEl) {
            console.warn('[y-live] Action element must be inside a data-live container');
            return;
        }

        if (modifiers.includes('prevent')) e.preventDefault();
        if (modifiers.includes('stop')) e.stopPropagation();
        if (el.disabled) return;

        if (eventType === 'submit' || (eventType === 'click' && (el.tagName === 'A' || el.type === 'submit'))) {
            e.preventDefault();
        }

        // 解析现代语法: actionName(expr)
        let actionName = content;
        let finalParams = {};

        const callMatch = content.match(/^([^(]+)(?:\((.*)\))?$/);
        if (callMatch) {
            actionName = callMatch[1].trim();
            const expr = callMatch[2];
            if (expr) {
                try {
                    // 使用 execute (即 evaluate) 求值
                    const evaluated = execute(expr);
                    if (evaluated !== null && typeof evaluated === 'object') {
                        finalParams = { ...finalParams, ...evaluated };
                    } else if (evaluated !== undefined) {
                        finalParams.value = evaluated;
                    }
                } catch (err) {
                    console.warn('[y-live] Action params evaluation failed:', expr, err);
                }
            }
        }

        // 如果是输入框，自动带上当前值 (除非已经有同名参数)
        if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            if (finalParams.value === undefined) {
                finalParams.value = el.type === 'checkbox' ? el.checked : el.value;
            }
        }

        // 兼容 data-action-params 和 data-live-action-params
        const paramsAttr = el.getAttribute('data-action-params') || el.getAttribute('data-live-action-params');
        if (paramsAttr) {
            try {
                if (paramsAttr.trim().startsWith('{')) {
                    finalParams = { ...finalParams, ...JSON.parse(paramsAttr) };
                } else {
                    const result = execute(paramsAttr);
                    if (result && typeof result === 'object') {
                        finalParams = { ...finalParams, ...result };
                    }
                }
            } catch (err) {}
        }

        const isStream = modifiers.includes('stream') || el.hasAttribute('data-stream');
        
        // 执行分发
        const liveStateRef = liveEl._y_live_state_ref;
        const liveComponentClass = liveEl._y_live_component_class;
        const liveState = liveEl._y_state;

        dispatchAction(liveEl, liveComponentClass, actionName, liveStateRef, liveState, e, finalParams, isStream);
    };

    el.addEventListener(eventType, handler);
    return () => el.removeEventListener(eventType, handler);
});

// 3. 注册 data-live-model 指令
directive('live-model', (el, state, method, { content, modifiers }) => {
    const property = content;
    const isText = (el.tagName === 'INPUT' && (el.type === 'text' || el.type === 'email')) || el.tagName === 'TEXTAREA';
    const eventType = isText ? 'input' : 'change';
    
    let timer = null;
    const handler = (e) => {
        clearTimeout(timer);
        const delay = parseInt(modifiers[modifiers.indexOf('debounce') + 1]) || 300;
        
        timer = setTimeout(() => {
            const liveEl = el.closest('[data-live]');
            if (!liveEl) return;

            const value = el.type === 'checkbox' ? el.checked : el.value;
            if (state) state[property] = value;

            dispatchAction(liveEl, liveEl._y_live_component_class, '__updateProperty', liveEl._y_live_state_ref, liveEl._y_state, e, {
                property,
                value
            });
        }, delay);
    };

    el.addEventListener(eventType, handler);
    return () => el.removeEventListener(eventType, handler);
});

async function dispatchAction(el, componentClass, action, stateRef, state, event, params = {}, isStream = false) {
    showProgress();

    if (isStream) {
        dispatchStream(el, componentClass, action, stateRef, state, event, params, 
            (chunk) => processStreamChunk(chunk, el, state, stateRef),
            () => {
                setLoading(el, false);
                hideProgress();
            }
        );
        return;
    }

    try {
        const result = await dispatchLive(el, componentClass, action, stateRef, state, event, params);
        if (!result.success) return;

        const data = result.data;
        applyLiveResponse(el, data, state, stateRef);

        // 处理 DOM Patches
        if (data.domPatches) {
            data.domPatches.forEach(patch => {
                const target = document.querySelector(patch.selector);
                if (target) {
                    replaceLiveHtml(target, patch.html, data.state);
                    initDirectives(target);
                }
            });
        }

        // 处理 Fragments (局部刷新)
        if (data.fragments) {
            data.fragments.forEach(fragment => {
                const liveEl = el.closest('[data-live]') || el;
                applyLiveFragment(liveEl, fragment, data.state);

                // 在更新后的目标元素及其子元素上重新注册指令
                const fragmentEl = liveEl.querySelector(`[data-live-fragment="${fragment.name}"]`);
                if (fragmentEl) {
                    initDirectives(fragmentEl);
                }
            });
        }

        // 处理 Operations
        if (data.operations) {
            data.operations.forEach(op => executeOperation(op));
        }
    } finally {
        hideProgress();
    }
}

let progressTimer = null;
function showProgress() {
    let el = document.getElementById('y-progress');
    if (!el) {
        el = document.createElement('div');
        el.id = 'y-progress';
        document.body.appendChild(el);
    }
    el.style.opacity = '1';
    el.style.width = '0%';
    
    // 强制重绘
    el.offsetWidth; 
    
    el.style.width = '30%';
    
    clearTimeout(progressTimer);
    progressTimer = setTimeout(() => {
        el.style.width = '70%';
    }, 500);
}

function hideProgress() {
    const el = document.getElementById('y-progress');
    if (!el) return;
    
    clearTimeout(progressTimer);
    el.style.width = '100%';
    
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => {
            el.style.width = '0%';
        }, 400);
    }, 300);
}

function processStreamChunk(data, el, state, stateRef) {
    if (data.type === 'live' || data.type === 'done') {
        applyLiveResponse(el, data, state, stateRef);
        if (data.operations) data.operations.forEach(op => executeOperation(op));
        if (data.type === 'done') hideProgress();
    }
}

// 初始化子模块
initIntl();
bindNavigateLinks(document);
Poll.autoInit(document);

const L = {
    dispatch: dispatchAction,
    executeOperation,
    navigate,
};

window.L = L;
export default L;
