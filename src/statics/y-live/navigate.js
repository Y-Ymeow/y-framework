// Navigate System - data-navigate 无刷新导航
import { createSafeFragment } from './core/dom.js';
import { setupLiveComponent } from './core/state.js';
import { initDirectives } from '../y-directive/directives.js';

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
        .y-progress-bar.active { opacity: 1; }
        .y-progress-bar.indeterminate { width: 30%; animation: y-progress-pulse 1s infinite; }
        @keyframes y-progress-pulse { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; } }
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

export function bindNavigateLinks(root) {
    root.querySelectorAll('a[data-navigate]').forEach(link => {
        if (link._y_navigate_bound) return;
        link._y_navigate_bound = true;

        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;

            e.preventDefault();

            const options = {};
            if (link.hasAttribute('data-navigate-replace')) options.replace = true;
            if (link.dataset.navigateFragment) options.fragment = link.dataset.navigateFragment;
            if (link.dataset.navigateState) {
                try { options.state = JSON.parse(link.dataset.navigateState); } catch (e) {}
            }

            navigate(href, options);
        });
    });
}

export async function navigate(url, options = {}) {
    const { replace = false, fragment = null, state = null } = options;

    showProgress();

    try {
        const response = await fetch('/live/navigate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Navigate': 'true',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ url, fragment, state }),
        });

        if (!response.ok) throw new Error('Navigate failed: ' + response.status);

        const data = await response.json();

        if (!data.fragments || !Array.isArray(data.fragments)) {
            throw new Error('Invalid navigate response');
        }

        const updateDom = () => {
            if (data.title) document.title = data.title;

            if (replace) {
                window.history.replaceState({ navigateUrl: url }, data.title || '', url);
            } else {
                window.history.pushState({ navigateUrl: url }, data.title || '', url);
            }

            data.fragments.forEach(fragment => {
                if (!fragment.name) return;
                const target = fragment.name === 'body' 
                    ? document.body 
                    : document.querySelector(`[data-navigate-fragment="${fragment.name}"]`);
                if (target) replaceNavigateHtml(target, fragment.html);
            });

            data.fragments.forEach(fragment => {
                if (!fragment.name) return;
                const target = fragment.name === 'body'
                    ? document.body
                    : document.querySelector(`[data-navigate-fragment="${fragment.name}"]`);
                if (target) bindNavigateLinks(target);
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

function replaceNavigateHtml(target, html) {
    target.replaceChildren(createSafeFragment(html));

    // 使用全局 L 来重新绑定所有指令和 Live 组件
    if (window.L && typeof window.L.boot === 'function' && target === document.body) {
        // 全量引导 —— body 替换的场景
        window.L.boot();
    } else {
        // 片段替换场景
        // 1. 引导核心指令系统 (Y-UI)
        if (window.Y && typeof window.Y.boot === 'function') {
            window.Y.boot(target);
        }

        // 2. 初始化 y-directive (data-text, data-on, data-bind 等)
        initDirectives(target);

        // 3. 引导 Live 组件
        const dispatchAction = window.L && window.L.dispatch;
        const liveEls = target.querySelectorAll('[data-live]');
        if (target.hasAttribute('data-live')) {
            setupLiveComponent(target, dispatchAction);
        }
        liveEls.forEach(el => {
            delete el._y_live_ready;
            setupLiveComponent(el, dispatchAction);
        });
    }

    // 4. 重新绑定导航链接
    bindNavigateLinks(target);
}
