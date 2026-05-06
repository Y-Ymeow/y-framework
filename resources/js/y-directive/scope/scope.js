// Scope - 作用域构建
export function getFullScope(el, state) {
    const proxy = (state && state.proxy) ? state.proxy : state;

    const localScopes = [];
    let curr = el;
    while (curr) {
        if (curr._y_local_scope) localScopes.push(curr._y_local_scope);
        curr = curr.parentElement;
    }

    const scopeProxy = new Proxy({}, {
        get(target, key) {
            if (key === '$') return scopeProxy;
            if (key === Symbol.unscopables) return undefined;

            for (const scope of localScopes) {
                if (key in scope) return scope[key];
            }

            if (proxy && key in proxy) return proxy[key];

            if (key in window) return window[key];

            return undefined;
        },
        set(target, key, value) {
            for (const scope of localScopes) {
                if (key in scope) {
                    scope[key] = value;
                    return true;
                }
            }

            if (proxy) {
                proxy[key] = value;
                return true;
            }
            return false;
        },
        has(target, key) {
            if (key === '$') return true;
            if (key === Symbol.unscopables) return false;
            
            for (const scope of localScopes) {
                if (key in scope) return true;
            }
            if (proxy && key in proxy) return true;
            if (key in window) return true;
            
            return false;
        }
    });

    return scopeProxy;
}

export function getRootState(el) {
    let rootState = null;
    let p = el;
    while (p) {
        if (p?._y_state) {
            rootState = p._y_state.proxy;
            break;
        }
        p = p?.parentElement;
    }
    return rootState;
}
