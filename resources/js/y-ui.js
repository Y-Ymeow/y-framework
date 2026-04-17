import { Idiomorph } from 'idiomorph/dist/idiomorph.esm.js';

/**
 * Y Framework 前端核心
 */
export const Y = {
    components: {},
    
    // 注册组件类 (比 Alpine 更容易扩展，支持 Swiper 等)
    register(name, componentClass) {
        this.components[name] = componentClass;
    },

    // 初始化所有组件
    boot() {
        document.querySelectorAll('[data-component]').forEach(el => {
            const name = el.getAttribute('data-component');
            if (this.components[name]) {
                const instance = new this.components[name](el);
                instance.mounted?.();
                el._y_instance = instance;
            }
        });

        // 绑定 Y-Live 事件 (替代 Livewire 的高效更新)
        document.addEventListener('click', e => {
            const actionEl = e.target.closest('[data-live-action]');
            if (actionEl) {
                this.liveCall(actionEl);
            }
        });
    },

    // 高效的片段更新 (只合并变更部分)
    async liveCall(el) {
        const component = el.closest('[data-live-id]');
        const componentId = component.getAttribute('data-live-id');
        const action = el.getAttribute('data-live-action');
        const formData = new FormData(component.querySelector('form') || document.createElement('form'));
        
        const response = await fetch(`${window.location.pathname}?_live=${action}`, {
            method: 'POST',
            body: formData,
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Live-Id': componentId
            }
        });

        const newHtml = await response.text();
        Idiomorph.morph(component, newHtml);
    }
};
