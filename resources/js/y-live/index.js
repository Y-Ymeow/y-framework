import { directive, initDirectives } from '../y-directive/index.js';
import { setupLiveComponent, applyLiveResponse } from './core/state.js';
import { dispatchAction as dispatchLiveAction, dispatchStream, setLoading } from './core/connection.js';
import { replaceLiveHtml, applyLiveFragment } from './core/dom.js';
import { executeOperation } from './operations.js';
import { initIntl } from './intl.js';
import { bindNavigateLinks, navigate } from './navigate.js';
import Poll from './poll.js';

// 1. 注册 data-live 指令
directive('live', (el, state, method, { content }) => {
    setupLiveComponent(el, () => {});
    el._y_live_managed = true;
});

// 2. 注册 data-action 指令 — 解析为 $live.actionName(params)
//    data-action:click="loadPage(1, 10)" → $live.loadPage(1, 10)
//    data-action="loadPage(1, 10)"      → $live.loadPage(1, 10) (默认 click)
directive('action', (el, state, method, { content, modifiers, $execute }) => {
    const eventType = method || 'click';

    const handler = (e) => {
        if (modifiers.includes('prevent')) e.preventDefault();
        if (modifiers.includes('stop')) e.stopPropagation();
        if (el.disabled) return;

        if (eventType === 'submit' || (eventType === 'click' && (el.tagName === 'A' || el.type === 'submit'))) {
            e.preventDefault();
        }

        let actionExpr = content;
        if (actionExpr.indexOf('(') === -1) {
            actionExpr += '()';
        }

        const paramsAttr = el.getAttribute('data-action-params');
        if (paramsAttr && actionExpr.endsWith('()')) {
            try {
                const params = JSON.parse(paramsAttr);
                actionExpr = actionExpr.slice(0, -2) + '(' + JSON.stringify(params) + ')';
            } catch (err) {
                console.warn('[y-live] Invalid data-action-params:', paramsAttr, err);
            }
        }

        $execute('$live.' + actionExpr, null, e);
    };

    if (el._y_action_bound) return;
    el._y_action_bound = true;

    el.addEventListener(eventType, handler);
    return () => {
        el._y_action_bound = false;
        el.removeEventListener(eventType, handler);
    };
});

// 3. 注册 data-live-model 指令 — 绑定输入到 $live.update(property, value)
directive('live-model', (el, state, method, { content, modifiers, effect, execute }) => {
    const property = content;
    const isBlur = modifiers.includes('blur');
    const isLive = modifiers.includes('live');
    
    // 确定监听事件
    const eventType = isBlur ? 'blur' : (
        (el.tagName === 'INPUT' && (el.type === 'text' || el.type === 'email' || el.type === 'password')) || el.tagName === 'TEXTAREA'
            ? 'input' : 'change'
    );

    let timer = null;
    const handleInput = (e) => {
        const value = el.type === 'checkbox' ? el.checked : el.value;
        
        // 1. 同步到本地 ReactiveState (y-directive 核心)
        if (state && typeof state.set === 'function') {
            state.set(property, value);
        }

        // 2. 如果标记了 .live，则同步到后端 /live/state
        if (isLive) {
            clearTimeout(timer);
            const delay = modifiers.includes('debounce') 
                ? (parseInt(modifiers[modifiers.indexOf('debounce') + 1]) || 300) 
                : (isBlur ? 0 : 300);

            timer = setTimeout(() => {
                const liveEl = el.closest('[data-live]');
                if (liveEl && liveEl.$live) {
                    liveEl.$live.update(property, value);
                }
            }, delay);
        }
    };

    el.addEventListener(eventType, handleInput);

    // 自动回填逻辑 (双向绑定)
    effect(() => {
        const val = state.get ? state.get(property) : state[property];
        if (el.type === 'checkbox') {
            if (el.checked !== !!val) el.checked = !!val;
        } else {
            if (el.value !== String(val ?? '')) el.value = val ?? '';
        }
    });

    return () => el.removeEventListener(eventType, handleInput);
});

// 4. 注册 data-submit 指令 — 收集表单数据一次性提交到 LiveAction
//    data-submit:click="saveSettings" → 收集 [data-model] 字段 → $live.saveSettings({field1: val1, ...})
directive('submit', (el, state, method, { content, modifiers, $execute }) => {
    const eventType = method || 'click';

    const handler = (e) => {
        if (modifiers.includes('prevent')) e.preventDefault();
        if (modifiers.includes('stop')) e.stopPropagation();
        if (el.disabled) return;

        e.preventDefault();

        const scope = el.closest('[data-state]') || el.closest('[data-live-state]') || el.parentElement;
        if (!scope) return;

        const formData = {};
        scope.querySelectorAll('[data-model]').forEach(input => {
            const key = input.getAttribute('data-model');
            if (!key) return;
            if (input.type === 'checkbox') {
                formData[key] = input.checked;
            } else if (input.tagName === 'SELECT') {
                formData[key] = input.value;
            } else if (input.tagName === 'TEXTAREA') {
                formData[key] = input.value;
            } else {
                formData[key] = input.value;
            }
        });

        scope.querySelectorAll('[data-submit-field]').forEach(input => {
            const key = input.getAttribute('data-submit-field');
            if (!key) return;
            if (input.type === 'checkbox') {
                formData[key] = input.checked;
            } else if (input.tagName === 'SELECT') {
                formData[key] = input.value;
            } else if (input.type === 'radio') {
                if (input.checked) formData[key] = input.value;
            } else {
                formData[key] = input.value;
            }
        });

        const actionName = content;
        const liveProxy = scope.$live;
        if (liveProxy && typeof liveProxy[actionName] === 'function') {
            liveProxy[actionName](formData);
        }
    };

    if (el._y_submit_bound) return;
    el._y_submit_bound = true;

    el.addEventListener(eventType, handler);
    return () => {
        el._y_submit_bound = false;
        el.removeEventListener(eventType, handler);
    };
});

// 5. 注册 data-live-upload 指令 — 文件上传
//    元素上 data-live-upload 标记文件上传区
//    选中文件后自动 POST 到 /live/upload，将 URL 回填到 data-media-value 的 hidden input
directive('live-upload', (el, state, method, { content }) => {
    const fileInput = el.querySelector('input[type="file"]');
    const hiddenInput = el.querySelector('[data-media-value]');
    if (!fileInput) return;

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const handleUpload = async (file) => {
        el.classList.add('y-uploading');
        const fd = new FormData();
        fd.append('file', file);

        try {
            const resp = await fetch('/live/upload', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken() },
                body: fd,
            });
            const data = await resp.json();
            if (data.success && hiddenInput) {
                hiddenInput.value = data.url;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                const preview = el.querySelector('.ux-form-media-preview');
                if (preview) {
                    preview.classList.add('has-image');
                    preview.classList.remove('empty');
                    const img = preview.querySelector('img');
                    if (img) img.src = data.url;
                    const placeholder = preview.querySelector('.ux-form-media-placeholder');
                    if (placeholder) placeholder.remove();
                }
            } else {
                alert(data.error || 'Upload failed');
            }
        } catch (err) {
            console.error('Upload error:', err);
            alert('Upload failed');
        } finally {
            el.classList.remove('y-uploading');
        }
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) handleUpload(file);
        fileInput.value = '';
    });

    el.addEventListener('dragover', (e) => { e.preventDefault(); el.classList.add('y-dragover'); });
    el.addEventListener('dragleave', () => el.classList.remove('y-dragover'));
    el.addEventListener('drop', (e) => {
        e.preventDefault();
        el.classList.remove('y-dragover');
        const file = e.dataTransfer.files[0];
        if (file) handleUpload(file);
    });

    const trigger = el.querySelector('[data-media-trigger]');
    if (trigger) {
        trigger.addEventListener('click', () => fileInput.click());
    }
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
        const result = await dispatchLiveAction(el, componentClass, action, stateRef, state, event, params);
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

        // 处理组件级 Events（子组件 emit 的事件冒泡到父组件）
        if (data.events && data.events.length > 0) {
            const liveEl = el.closest('[data-live]') || el;
            data.events.forEach(evt => {
                const eventName = 'live:' + evt.event;
                const customEvent = new CustomEvent(eventName, {
                    detail: evt.params || {},
                    bubbles: true,
                    cancelable: true,
                });
                liveEl.dispatchEvent(customEvent);

                // 如果有父级 Live 组件，也 dispatch 到父级
                const parentEl = liveEl.parentElement?.closest('[data-live]');
                if (parentEl && parentEl !== liveEl) {
                    parentEl.dispatchEvent(new CustomEvent(eventName, {
                        detail: evt.params || {},
                        bubbles: true,
                        cancelable: true,
                    }));
                }
            });
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
    getLive: (el) => {
        const liveEl = el?.closest?.('[data-live]') || el
        return liveEl?.$live || null
    },
};

window.L = L;
export default L;
