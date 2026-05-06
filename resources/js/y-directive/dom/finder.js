// DOM Finder - 查找父级状态和局部作用域
export function findParentState(el) {
    let current = el;
    while (current) {
        if (current._y_state) {
            return current._y_state;
        }
        // 特殊处理：有些元素可能是由外部库（如 Live）注入的，尝试通过 data-state 属性查找
        if (current.hasAttribute && current.hasAttribute('data-state') && !current._y_state) {
            // 这种情况下 binder 应该已经初始化了它，如果没有，说明顺序有问题
            // 但为了鲁棒性，我们在这里不做即时初始化，而是依靠 binder 的顺序
        }
        current = current.parentElement || current.parentNode;
    }
    return null;
}

export function findLocalScope(el) {
    const scopes = [];
    let current = el;
    while (current) {
        if (current._y_local_scope) {
            scopes.push(current._y_local_scope);
        }
        current = current.parentElement;
    }
    return scopes;
}

export function findTemplate(el) {
    return el.querySelector('template');
}
