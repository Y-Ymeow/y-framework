// Reactive Context - 全局响应式上下文
if (!globalThis.__Y_CONTEXT__) {
    globalThis.__Y_CONTEXT__ = {
        reactive: {
            activeEffect: null,
            targetMap: new WeakMap(),
            batchQueue: [],
            isBatching: false
        },
        directive: {
            registry: new Map(),
            exprCache: new Map(),
            execCache: new Map(),
            boundElements: new WeakSet()
        }
    };
}

export const context = globalThis.__Y_CONTEXT__;
export const reactiveContext = context.reactive;
export const directiveContext = context.directive;
