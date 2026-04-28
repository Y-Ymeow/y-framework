let currentTracker = null;
const trackerStack = [];
let batchDepth = 0;
const pendingEffects = new Set();

function createSignal(value) {
    const subscribers = new Set();
    return {
        get() {
            if (currentTracker) {
                currentTracker.deps.add(subscribers);
                subscribers.add(currentTracker.fn);
            }
            return value;
        },
        set(newValue) {
            if (Object.is(value, newValue)) return;
            value = typeof newValue === 'function' ? newValue(value) : newValue;
            if (batchDepth > 0) {
                subscribers.forEach(fn => pendingEffects.add(fn));
            } else {
                [...subscribers].forEach(fn => fn());
            }
        },
        peek: () => value,
        subscribers,
    };
}

function effect(fn) {
    let deps = new Set();
    const run = () => {
        deps.forEach(dep => dep.delete(run));
        deps.clear();
        const prev = currentTracker;
        currentTracker = { fn: run, deps };
        trackerStack.push(currentTracker);
        try { fn(); } finally {
            trackerStack.pop();
            currentTracker = trackerStack[trackerStack.length - 1] || null;
        }
    };
    run();
    return () => { deps.forEach(dep => dep.delete(run)); deps.clear(); };
}

function computed(fn) {
    const sig = createSignal(undefined);
    effect(() => sig.set(fn()));
    return sig;
}

function batch(fn) {
    batchDepth++;
    try { fn(); } finally {
        batchDepth--;
        if (batchDepth === 0) {
            const effects = [...pendingEffects];
            pendingEffects.clear();
            effects.forEach(fn => fn());
        }
    }
}

class ReactiveState {
    constructor(data = {}) {
        this._signals = {};
        this._disposers = [];
        for (const key in data) {
            this._signals[key] = createSignal(data[key]);
        }
        const state = this;
        this.proxy = new Proxy(this, {
            get(target, prop) {
                if (prop === '__state') return target;
                if (target._signals[prop]) return target._signals[prop].get();
                return undefined;
            },
            set(target, prop, value) {
                if (target._signals[prop]) {
                    target._signals[prop].set(value);
                } else {
                    target._signals[prop] = createSignal(value);
                }
                return true;
            },
            has(target, prop) {
                return prop in target._signals;
            },
            ownKeys(target) {
                return Object.keys(target._signals);
            },
            getOwnPropertyDescriptor() {
                return { configurable: true, enumerable: true };
            },
        });
    }

    get(key) { return this.proxy[key]; }
    set(key, value) { this.proxy[key] = value; }

    addDisposer(disposer) {
        this._disposers.push(disposer);
    }

    dispose() {
        this._disposers.forEach(d => d());
        this._disposers = [];
    }

    merge(data) {
        batch(() => {
            for (const key in data) {
                this.proxy[key] = data[key];
            }
        });
    }

    toJSON() {
        const result = {};
        for (const key in this._signals) {
            result[key] = this._signals[key].peek();
        }
        return result;
    }
}

export { createSignal, effect, computed, batch, ReactiveState };
