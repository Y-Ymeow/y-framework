// Reactive State - ReactiveState 类
import { track, trigger } from './core.js';
import { batch } from './effect.js';

export class ReactiveState {
    constructor(initial = {}) {
        this._raw = { ...initial };
        this._listeners = new Map();
        this.proxy = this._createProxy();
    }

    _createProxy() {
        const self = this;
        return new Proxy(this._raw, {
            get(target, key) {
                if (key === '_isReactive') return true;
                track(target, key);
                return target[key];
            },
            set(target, key, value) {
                const oldVal = target[key];
                target[key] = value;
                if (!Object.is(oldVal, value)) {
                    trigger(target, key);
                }
                return true;
            }
        });
    }

    set(key, value) {
        this.proxy[key] = value;
    }

    get(key) {
        return this.proxy[key];
    }

    merge(data) {
        batch(() => {
            Object.keys(data).forEach(key => {
                this.proxy[key] = data[key];
            });
        });
    }

    all() {
        return { ...this._raw };
    }
}
