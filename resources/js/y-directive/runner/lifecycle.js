// Runner Lifecycle - 指令生命周期管理
const elementEffects = new WeakMap();

export function addEffect(el, cleanupFn) {
    if (!elementEffects.has(el)) {
        elementEffects.set(el, []);
    }
    elementEffects.get(el).push(cleanupFn);
}

export function cleanupElement(el) {
    const effects = elementEffects.get(el);
    if (effects) {
        effects.forEach(fn => fn());
        elementEffects.delete(el);
    }
}

export function cleanupAll() {
    elementEffects.clear();
}
