// Reactive Effect - effect / batch / queueEffect
import { reactiveContext } from './context.js';

export function effect(fn) {
    const effectFn = () => {
        try {
            reactiveContext.activeEffect = effectFn;
            return fn();
        } finally {
            reactiveContext.activeEffect = null;
        }
    };
    effectFn();
    return effectFn;
}

export function batch(fn) {
    if (reactiveContext.isBatching) {
        fn();
        return;
    }
    reactiveContext.isBatching = true;
    try {
        fn();
    } finally {
        reactiveContext.isBatching = false;
        const queue = [...reactiveContext.batchQueue];
        reactiveContext.batchQueue = [];
        // 使用 Set 去重，并确保按顺序执行
        const uniqueQueue = new Set(queue);
        uniqueQueue.forEach(fn => fn());
    }
}

export function queueEffect(fn) {
    if (reactiveContext.isBatching) {
        reactiveContext.batchQueue.push(fn);
    } else {
        fn();
    }
}
