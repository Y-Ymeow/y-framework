/**
 * SSE 客户端 — Server-Sent Events 连接管理
 *
 * 封装 EventSource API，提供：
 * - 自动重连
 * - 心跳检测
 * - 事件分发
 * - 频道订阅
 */

const SseClient = {
    connections: new Map(),

    /**
     * 创建 SSE 连接
     *
     * @param {string} url - SSE 端点 URL
     * @param {object} options - 配置选项
     * @param {function} options.onMessage - 消息回调
     * @param {function} options.onOpen - 连接打开回调
     * @param {function} options.onError - 错误回调
     * @param {number} options.retryDelay - 重连延迟（毫秒）
     * @param {number} options.maxRetries - 最大重试次数
     * @returns {object} 连接控制器
     */
    connect(url, options = {}) {
        const {
            onMessage = () => {},
            onOpen = () => {},
            onError = () => {},
            onEvent = {},
            retryDelay = 3000,
            maxRetries = 5
        } = options;

        let retryCount = 0;
        let eventSource = null;
        let isManualClose = false;

        const connect = () => {
            eventSource = new EventSource(url);

            eventSource.onopen = () => {
                retryCount = 0;
                onOpen();
                console.log('[SSE] Connected:', url);
            };

            eventSource.onerror = (error) => {
                console.error('[SSE] Error:', error);

                if (isManualClose) return;

                eventSource.close();

                if (retryCount < maxRetries) {
                    retryCount++;
                    console.log(`[SSE] Reconnecting in ${retryDelay}ms (attempt ${retryCount}/${maxRetries})`);
                    setTimeout(connect, retryDelay);
                } else {
                    onError(new Error('Max retries exceeded'));
                }
            };

            // 默认消息处理
            eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    onMessage(data, event);
                } catch (e) {
                    onMessage(event.data, event);
                }
            };

            // 注册自定义事件监听
            for (const [eventName, callback] of Object.entries(onEvent)) {
                eventSource.addEventListener(eventName, (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        callback(data, event);
                    } catch (e) {
                        callback(event.data, event);
                    }
                });
            }

            // 内置事件
            eventSource.addEventListener('ping', (event) => {
            });

            eventSource.addEventListener('close', (event) => {
                isManualClose = true;
                eventSource.close();
            });
        };

        connect();

        const controller = {
            close: () => {
                isManualClose = true;
                if (eventSource) {
                    eventSource.close();
                }
            },
            reconnect: () => {
                isManualClose = false;
                retryCount = 0;
                if (eventSource) {
                    eventSource.close();
                }
                connect();
            },
            getReadyState: () => eventSource?.readyState
        };

        this.connections.set(url, controller);
        return controller;
    },

    /**
     * 从 LiveAction 创建 SSE 连接
     *
     * @param {string} componentId - Live 组件 ID
     * @param {string} action - Action 名称
     * @param {object} options - 配置选项
     */
    fromAction(componentId, action, options = {}) {
        const url = new URL(window.location.href);
        url.searchParams.set('live', componentId);
        url.searchParams.set('action', action);

        return this.connect(url.toString(), options);
    },

    /**
     * 关闭指定连接
     */
    disconnect(url) {
        const controller = this.connections.get(url);
        if (controller) {
            controller.close();
            this.connections.delete(url);
        }
    },

    /**
     * 关闭所有连接
     */
    disconnectAll() {
        for (const controller of this.connections.values()) {
            controller.close();
        }
        this.connections.clear();
    },

    /**
     * 简化的消息订阅
     *
     * @param {string} url - SSE 端点
     * @param {function} callback - 消息回调
     * @returns {function} 取消订阅函数
     */
    subscribe(url, callback) {
        const controller = this.connect(url, {
            onMessage: callback
        });

        return () => controller.close();
    }
};

export default SseClient;
