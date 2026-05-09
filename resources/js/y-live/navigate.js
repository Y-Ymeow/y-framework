// Navigate System - data-navigate 无刷新导航
import { createSafeFragment } from './core/dom.js';
import { setupLiveComponent } from './core/state.js';
import { initDirectives } from '../y-directive';

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
            if (isExternalUrl(href)) return;

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

function isExternalUrl(href) {
    try {
        const url = new URL(href, window.location.origin);
        return url.origin !== window.location.origin;
    } catch {
        return false;
    }
}

function loadSources(sources) {
    const cssList = sources.css || [];
    const jsList = sources.js || [];
    const promises = [];

    cssList.forEach(item => {
        if (!item.href) return;
        const existingByHref = document.querySelector(`link[href="${item.href}"]`);
        if (existingByHref) return;
        const existingById = item.id ? document.querySelector(`link[id="${item.id}"]`) : null;
        promises.push(new Promise((resolve) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = item.href;
            link.onload = () => {
                if (existingById && existingById !== link) existingById.remove();
                resolve();
            };
            link.onerror = () => {
                if (existingById && existingById !== link) existingById.remove();
                resolve();
            };
            document.head.appendChild(link);
            if (item.id) {
                if (existingById) existingById.removeAttribute('id');
                link.id = item.id;
            }
        }));
    });

    jsList.forEach(item => {
        if (!item.src) return;
        const existingByHref = document.querySelector(`script[src="${item.src}"]`);
        if (existingByHref) return;
        const existingById = item.id ? document.querySelector(`script[id="${item.id}"]`) : null;
        promises.push(new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = item.src;
            if (item.module) script.type = 'module';
            script.defer = true;
            script.onload = () => {
                if (existingById && existingById !== script) existingById.remove();
                resolve();
            };
            script.onerror = () => {
                if (existingById && existingById !== script) existingById.remove();
                resolve();
            };
            document.head.appendChild(script);
            if (item.id) {
                if (existingById) existingById.removeAttribute('id');
                script.id = item.id;
            }
        }));
    });

    return Promise.all(promises);
}

export async function navigate(url, options = {}) {
    const { replace = false, fragment = null, state = null } = options;

    showProgress();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/live/navigate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Navigate': 'true',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
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

            loadSources(data.sources || {}).then(() => {
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

                applyActiveState(url);
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

    // 4. 重新初始化 UX 组件
    if (window.UX && typeof window.UX._registry === 'object') {
        window.UX._registry.forEach((component, name) => {
            if (typeof component.init === 'function') {
                try { component.init(); } catch (e) { console.error(`UX [${name}] re-init failed:`, e); }
            }
        });
    }

    // 5. 重新绑定导航链接
    bindNavigateLinks(target);
}

export function applyActiveState(url) {
    document.querySelectorAll('[data-active-class]').forEach(el => {
        const activeClass = el.dataset.activeClass;
        const targetSelector = el.dataset.activeTarget;
        if (!activeClass) return;

        const target = targetSelector ? el.closest(targetSelector) : el.parentElement;
        if (!target) return;

        const href = el.getAttribute('href') || '';
        let isActive = false;

        if (href && href !== 'javascript:;') {
            var normalizedUrl = url.replace(/\/+$/, '') || '/';
            var normalizedHref = href.replace(/\/+$/, '') || '/';
            if (normalizedUrl === normalizedHref) {
                isActive = true;
            }
        }

        if (isActive) {
            target.classList.add(...activeClass.split(/\s+/));
        } else {
            target.classList.remove(...activeClass.split(/\s+/));
        }
    });
}

// 初始加载时也应用
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        applyActiveState(window.location.pathname);
    });
}
