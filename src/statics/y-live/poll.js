/**
 * Poll 轮询客户端 — 基于 LiveComponent dispatch 的定时更新
 *
 * 通过 data-poll 属性自动发现轮询配置，
 * 每次轮询走标准 /live/update 流程，
 * 响应的 patches/operations 自动更新 DOM。
 *
 * condition 只决定是否执行 dispatch，不停止轮询。
 * 轮询始终运行，当 condition 为 false 时跳过请求。
 */

const Poll = {
    timers: new Map(),

    start(key, fetcher, options = {}) {
        const {
            interval = 5000,
            immediate = true,
            onData = () => {},
            onError = () => {},
            condition = null
        } = options;

        if (this.timers.has(key)) {
            this.stop(key);
        }

        let shouldContinue = true;
        let pollCount = 0;

        const poll = async () => {
            if (!shouldContinue) return;

            try {
                const data = await fetcher();
                pollCount++;

                if (condition) {
                    if (typeof condition === 'function') {
                        shouldContinue = condition(data, pollCount);
                    }
                }

                onData(data, pollCount);

                if (!shouldContinue) {
                    this.stop(key);
                }
            } catch (error) {
                onError(error, pollCount);
            }
        };

        if (immediate) {
            poll();
        }

        const timerId = setInterval(poll, interval);

        const controller = {
            stop: () => {
                shouldContinue = false;
                clearInterval(timerId);
                this.timers.delete(key);
            },
            pause: () => {
                clearInterval(timerId);
            },
            resume: () => {
                if (shouldContinue) {
                    const newTimerId = setInterval(poll, interval);
                    this.timers.set(key, { ...this.timers.get(key), timerId: newTimerId });
                }
            },
            getCount: () => pollCount
        };

        this.timers.set(key, { timerId, controller, fetcher, options });
        return controller;
    },

    stop(key) {
        const item = this.timers.get(key);
        if (item) {
            item.controller.stop();
        }
    },

    stopAll() {
        for (const key of this.timers.keys()) {
            this.stop(key);
        }
    },

    fromLiveAction(liveEl, action, options = {}) {
        const componentId = liveEl.dataset.liveId || '';
        const key = `${componentId}:${action}`;

        const {
            interval = 5000,
            immediate = true,
            condition = null
        } = options;

        const poll = async () => {
            const state = liveEl._y_state;
            const stateRef = liveEl._y_live_state_ref;
            const componentClass = liveEl.dataset.live;

            if (!componentClass || !window.L || !window.L.dispatch) return;

            if (condition && state) {
                try {
                    const stateData = state.all();
                    const shouldRun = new Function('$', `with($) { return ${condition} }`)(stateData);
                    if (!shouldRun) return;
                } catch (e) {
                    return;
                }
            }

            try {
                await window.L.dispatch(liveEl, componentClass, action, stateRef, state, null, {});
            } catch (error) {
                console.error('[Poll] Error:', error);
            }
        };

        if (immediate) {
            poll();
        }

        const timerId = setInterval(poll, interval);

        const controller = {
            stop: () => {
                clearInterval(timerId);
                this.timers.delete(key);
            },
            getCount: () => 0
        };

        this.timers.set(key, { timerId, controller });
        return controller;
    },

    autoInit(root = document) {
        const elements = root.querySelectorAll('[data-poll]');

        elements.forEach((el) => {
            const liveEl = el.closest('[data-live]') || el;
            if (!liveEl.dataset.live) return;

            let pollConfig;
            try {
                pollConfig = JSON.parse(el.dataset.poll);
            } catch (e) {
                pollConfig = { [el.dataset.poll]: { interval: 5000 } };
            }

            for (const [action, cfg] of Object.entries(pollConfig)) {
                if (!action) continue;

                this.fromLiveAction(liveEl, action, {
                    interval: cfg.interval || 5000,
                    immediate: cfg.immediate !== false,
                    condition: cfg.condition || null,
                });
            }
        });
    }
};

export default Poll;
