// Y-UX - 组件交互层（按需加载版本）
import UX from './core/base.js';
import QRCodeLib from 'qrcode';

// 组件注册中心
const registry = new Map();
const initialized = new Set();

const UXFramework = {
    ...UX,

    // 组件注册中心
    _registry: registry,
    _initialized: initialized,

    /**
     * 注册组件
     * @param {string} name - 组件名称（如 'modal', 'tabs'）
     * @param {object} component - 组件对象，必须包含 init() 方法
     */
    register(name, component) {
        if (registry.has(name)) {
            return;
        }
        registry.set(name, component);

        // 如果 DOM 已就绪，立即初始化
        if (document.readyState !== 'loading') {
            this._initComponent(name, component);
        }
    },

    /**
     * 获取已注册的组件
     * @param {string} name - 组件名称
     * @returns {object|undefined}
     */
    get(name) {
        return registry.get(name);
    },

    /**
     * 检查组件是否已注册
     * @param {string} name - 组件名称
     * @returns {boolean}
     */
    has(name) {
        return registry.has(name);
    },

    /**
     * 初始化单个组件（内部）
     */
    _initComponent(name, component) {
        if (initialized.has(name)) return;
        initialized.add(name);

        if (typeof component.init === 'function') {
            try {
                component.init();
            } catch (e) {
                console.error(`UX component [${name}] init failed:`, e);
            }
        }
    },

    /**
     * 初始化所有已注册的组件
     */
    init() {
        UX.registerSafeAttrs();
        this.bindModalTriggers();

        registry.forEach((component, name) => {
            this._initComponent(name, component);
        });

        if (window.L) this.hookLive(window.L);
        window.addEventListener('l:ready', (e) => this.hookLive(e.detail || window.L));
    },

    bindModalTriggers() {
        if (this._modalTriggersBound) return;
        this._modalTriggersBound = true;

        document.addEventListener('click', (e) => {
            const open = e.target.closest?.('[data-ux-modal-open]');
            if (open) {
                e.preventDefault();
                this.openModal(open.getAttribute('data-ux-modal-open'));
                return;
            }

            const close = e.target.closest?.('[data-ux-modal-close]');
            if (close) {
                e.preventDefault();
                this.closeModal(close.getAttribute('data-ux-modal-close'));
                return;
            }

            if (e.target.classList?.contains('ux-modal-backdrop')) {
                this.closeModal(e.target.getAttribute('data-ux-modal-close') || null);
            }
        });
    },

    openModal(id) {
        const modal = id ? document.getElementById(id) : null;
        if (!modal) return;
        modal.classList.add('ux-modal-open');
        modal.setAttribute('data-visible', 'true');
        document.body.style.overflow = 'hidden';
    },

    closeModal(id = null) {
        const modal = id ? document.getElementById(id) : document.querySelector('.ux-modal-open');
        if (!modal) return;
        modal.classList.remove('ux-modal-open');
        modal.removeAttribute('data-visible');
        if (!document.querySelector('.ux-modal-open')) {
            document.body.style.overflow = '';
        }
    },

    hookLive(L) {
        if (!L || L._ux_hooked) return;
        L._ux_hooked = true;

        const originalExecute = L.executeOperation;
        L.executeOperation = (op) => {
            if (op.op && op.op.startsWith('ux:')) {
                const componentName = op.op.split(':')[1];
                const component = registry.get(componentName);

                if (!component) {
                    console.warn(`UX component [${componentName}] not registered, operation skipped.`);
                    return;
                }

                // 优先使用组件自定义的 liveHandler 处理操作映射
                if (typeof component.liveHandler === 'function') {
                    component.liveHandler(op);
                    return;
                }

                // 通用回退：按 action 名称直接调用组件方法
                const action = op.action;
                if (action && typeof component[action] === 'function') {
                    const fn = component[action];
                    if (fn.length >= 2 && (op.id !== undefined || op.value !== undefined)) {
                        const args = [op.id, op.value ?? op.index ?? op.tabId].filter(a => a !== undefined);
                        fn.apply(component, args);
                    } else {
                        fn.call(component, op);
                    }
                    return;
                }

                console.warn(`UX component [${componentName}] has no handler for action "${action}".`);
                return;
            }
            return originalExecute.call(L, op);
        };
    }
};

// 将核心方法暴露到 window.UX，但组件是动态注册的
window.UX = UXFramework;
window.UX.QRCodeLib = QRCodeLib;

// DOM 就绪后初始化所有已注册的组件
document.addEventListener('DOMContentLoaded', () => UXFramework.init());

export default UXFramework;
