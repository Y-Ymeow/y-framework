// Reactive Core - track / trigger 依赖收集
import { reactiveContext } from './context.js';
import { queueEffect } from './effect.js';

export function track(target, key) {
    if (!reactiveContext.activeEffect) return;

    let depsMap = reactiveContext.targetMap.get(target);
    if (!depsMap) {
        depsMap = new Map();
        reactiveContext.targetMap.set(target, depsMap);
    }

    let dep = depsMap.get(key);
    if (!dep) {
        dep = new Set();
        depsMap.set(key, dep);
    }

    dep.add(reactiveContext.activeEffect);
}

export function trigger(target, key) {
    const depsMap = reactiveContext.targetMap.get(target);
    if (!depsMap) return;

    const dep = depsMap.get(key);
    if (dep) {
        dep.forEach(effect => queueEffect(effect));
    }
}
