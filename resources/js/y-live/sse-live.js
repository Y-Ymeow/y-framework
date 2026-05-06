/**
 * SSE Live 客户端 — 与 LiveComponent 深度集成
 *
 * SSE 收到 live:action 事件时，通过 L.dispatch() 调用 LiveAction，
 * 走标准 /live/update 流程，利用 Live 的 patches/fragments/operations 更新页面。
 *
 * 数据流:
 *   SseHub::liveAction(componentId, action, params, channel)
 *     → SSE Endpoint 推送 event: live:action
 *     → 前端 SseLive 收到
 *     → L.dispatch(liveEl, componentClass, action, stateRef, state, null, params)
 *     → POST /live/update
 *     → 返回 patches/fragments/operations
 *     → Live 框架自动更新 DOM
 */

import SseClient from './sse.js';

const SseLive = {
    connection: null,
    config: null,

    init(config = null) {
        if (this.connection) return;

        if (!config) {
            const meta = document.querySelector('meta[name="sse-config"]');
            if (meta) {
                try {
                    config = JSON.parse(meta.content);
                } catch (e) {
                    console.warn('[SseLive] Invalid SSE config');
                    return;
                }
            }
        }

        if (!config || !config.token) return;

        this.config = config;
        this.connect();
    },

    connect() {
        if (!this.config) return;

        this.connection = SseClient.connect(this.config.endpoint, {
            onEvent: {
                'connected': (data) => {
                    console.log('[SseLive] Connected');
                },

                'live:action': (data) => {
                    this.handleLiveAction(data);
                },

                'live:state': (data) => {
                    this.handleLiveState(data);
                },

                'message': (data) => {
                    document.dispatchEvent(new CustomEvent('sse:message', { detail: data }));
                },

                'ping': () => {},

                'close': (data) => {
                    console.log('[SseLive] Server closed:', data.reason);
                }
            },

            onError: (error) => {
                console.error('[SseLive] Connection error:', error);
            }
        });
    },

    handleLiveAction(data) {
        const { componentId, action, params } = data;
        if (!componentId || !action) return;

        const liveEl = document.querySelector(`[data-live-id="${componentId}"]`);
        if (!liveEl) {
            console.warn('[SseLive] Component not found:', componentId);
            return;
        }

        if (!window.L || !window.L.dispatch) {
            console.warn('[SseLive] L.dispatch not available');
            return;
        }

        const state = liveEl._y_state;
        const stateRef = liveEl._y_live_state_ref;
        const componentClass = liveEl.dataset.live;

        if (!componentClass || !state || !stateRef) {
            console.warn('[SseLive] Missing component data for:', componentId);
            return;
        }

        window.L.dispatch(liveEl, componentClass, action, stateRef, state, null, params || {});
    },

    handleLiveState(data) {
        const { componentId, state: stateData } = data;
        if (!componentId || !stateData) return;

        const liveEl = document.querySelector(`[data-live-id="${componentId}"]`);
        if (!liveEl) return;

        if (liveEl._y_state) {
            liveEl._y_state.merge(stateData);
        }

        liveEl.dispatchEvent(new CustomEvent('live:state', { detail: stateData }));
    },

    disconnect() {
        if (this.connection) {
            this.connection.close();
            this.connection = null;
        }
    },

    reconnect() {
        this.disconnect();
        this.connect();
    }
};

if (typeof window !== 'undefined') {
    function tryInit() {
        if (window.L) {
            SseLive.init();
            return true;
        }
        return false;
    }

    if (!tryInit()) {
        window.addEventListener('l:ready', () => {
            SseLive.init();
        });
    }
}

export default SseLive;
