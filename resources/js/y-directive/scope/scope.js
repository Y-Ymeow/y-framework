// Scope - 作用域构建
export function registerScopeVar(el, name, value) {
    if (!el._y_scope_vars) el._y_scope_vars = {}
    el._y_scope_vars[name] = value
}

export function getFullScope(el, state) {
    const proxy = (state && state.proxy) ? state.proxy : state

    const localScopes = []
    const scopeVars = []
    let curr = el
    while (curr) {
        if (curr._y_local_scope) localScopes.push(curr._y_local_scope)
        if (curr._y_scope_vars) scopeVars.push(curr._y_scope_vars)
        curr = curr.parentElement
    }

    const scopeProxy = new Proxy({}, {
        get(target, key) {
            if (key === '$') return scopeProxy
            if (key === Symbol.unscopables) return undefined

            for (const scope of localScopes) {
                if (key in scope) return scope[key]
            }

            if (typeof key === 'string' && key.startsWith('$')) {
                for (const vars of scopeVars) {
                    if (key in vars) return vars[key]
                }
            }

            if (proxy && key in proxy) return proxy[key]

            if (key in window) return window[key]

            return undefined
        },
        set(target, key, value) {
            for (const scope of localScopes) {
                if (key in scope) {
                    scope[key] = value
                    return true
                }
            }

            if (typeof key === 'string' && key.startsWith('$')) return false

            if (proxy) {
                proxy[key] = value
                return true
            }
            return false
        },
        has(target, key) {
            if (key === '$') return true
            if (key === Symbol.unscopables) return false

            for (const scope of localScopes) if (key in scope) return true

            if (typeof key === 'string' && key.startsWith('$')) {
                for (const vars of scopeVars) if (key in vars) return true
            }

            if (proxy && key in proxy) return true
            if (key in window) return true

            return false
        }
    })

    return scopeProxy
}

export function getRootState(el) {
    while (el) {
        if (el._y_state) return el._y_state
        el = el.parentElement
    }
    return null
}
