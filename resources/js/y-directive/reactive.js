// Reactive System - 响应式核心
if (!globalThis.__Y_REACTIVE_CONTEXT__) {
    globalThis.__Y_REACTIVE_CONTEXT__ = {
        activeEffect: null,
        targetMap: new WeakMap(),
        batchQueue: [],
        isBatching: false
    };
}

var context = globalThis.__Y_REACTIVE_CONTEXT__;

export function track(target, key) {
    if (!context.activeEffect) return;
    let depsMap = context.targetMap.get(target);
    if (!depsMap) {
        depsMap = new Map();
        context.targetMap.set(target, depsMap);
    }
    let dep = depsMap.get(key);
    if (!dep) {
        dep = new Set();
        depsMap.set(key, dep);
    }
    dep.add(context.activeEffect);
}

export function trigger(target, key) {
    const depsMap = context.targetMap.get(target);
    if (!depsMap) return;
    const dep = depsMap.get(key);
    if (dep) {
        dep.forEach(effect => effect());
    }
}

export function effect(fn) {
    context.activeEffect = fn;
    fn();
    context.activeEffect = null;
}

export function batch(fn) {
    if (context.isBatching) {
        fn();
        return;
    }
    context.isBatching = true;
    fn();
    context.isBatching = false;
    const queue = [...context.batchQueue];
    context.batchQueue = [];
    queue.forEach(fn => fn());
}

export function queueEffect(fn) {
    if (context.isBatching) {
        context.batchQueue.push(fn);
    } else {
        fn();
    }
}

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

export { effect as $effect, batch as $batch };
