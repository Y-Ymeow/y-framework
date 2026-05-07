import { dispatchLive, getComponentInfo, updateLiveStateAttr } from './connection.js'
import { replaceLiveHtml, applyLiveFragment } from './dom.js'
import { initDirectives } from '../../y-directive/index.js'
import { executeOperation } from '../operations.js'
import { batch } from '../../y-directive/reactive/index.js'

function showLiveProgress() {
    let el = document.getElementById('y-progress')
    if (!el) {
        el = document.createElement('div')
        el.id = 'y-progress'
        el.style.cssText = 'position:fixed;top:0;left:0;height:2px;background:#3b82f6;z-index:9999;transition:width .3s;width:0;opacity:0'
        document.body.appendChild(el)
    }
    el.style.opacity = '1'
    el.style.width = '0%'
    el.offsetWidth
    el.style.width = '30%'
    setTimeout(() => { el.style.width = '70%' }, 500)
}

function hideLiveProgress() {
    const el = document.getElementById('y-progress')
    if (!el) return
    el.style.width = '100%'
    setTimeout(() => {
        el.style.opacity = '0'
        setTimeout(() => { el.style.width = '0%' }, 400)
    }, 200)
}

function parseActionArgs(args) {
    if (args.length === 0) return {}

    if (args.length === 1 && args[0] && typeof args[0] === 'object' && !Array.isArray(args[0])) {
        return args[0]
    }

    const result = {}
    args.forEach((val, i) => { result[i] = val })
    return result
}

async function callActionViaProxy(el, state, action, params) {
    const info = getComponentInfo(el)
    const componentClass = info.__component

    if (!componentClass) return

    showLiveProgress()

    try {
        const result = await dispatchLive(el, componentClass, action, { value: info.__state }, state, null, params)

        if (result && result.success) {
            const data = result.data

            if (data.state) {
                updateLiveStateAttr(el, data.state, data.patches)
            }

            if (data.patches && state && typeof state.merge === 'function') {
                batch(() => {
                    state.merge(data.patches)
                })
            }

            if (data.domPatches) {
                data.domPatches.forEach(patch => {
                    const target = document.querySelector(patch.selector)
                    if (target) {
                        replaceLiveHtml(target, patch.html, data.state)
                        initDirectives(target)
                    }
                })
            }

            if (data.fragments) {
                data.fragments.forEach(fragment => {
                    const liveEl = el.closest('[data-live]') || el
                    applyLiveFragment(liveEl, fragment, data.state)
                    const fragmentEl = liveEl.querySelector(`[data-live-fragment="${fragment.name}"]`)
                    if (fragmentEl) {
                        initDirectives(fragmentEl)
                        fragmentEl.dispatchEvent(new CustomEvent('y:updated', {
                            bubbles: true,
                            detail: { el: fragmentEl }
                        }))
                    }
                })
            }

            if (data.operations) {
                data.operations.forEach(op => executeOperation(op))
            }
        }
    } catch (err) {
        console.error('[y-live] Proxy action error:', err)
    } finally {
        hideLiveProgress()
    }
}

function dispatchRefresh(el, state, name) {
    const liveEl = el.closest('[data-live]') || el
    callActionViaProxy(liveEl, state, '__refresh', { fragment: name || null })
}

export function createLiveProxy(el, state, actions) {
    return new Proxy({}, {
        get(target, prop) {
            if (prop === 'loading') {
                return el.closest('[data-live]')?.classList.contains('y-loading-root') || false
            }

            if (prop === 'get') {
                return () => {
                    if (state && typeof state.all === 'function') return state.all()
                    return state ? { ...state } : {}
                }
            }

            if (prop === 'refresh') {
                return (name) => dispatchRefresh(el, state, name)
            }

            if (prop === 'dispatch') {
                return (eventName, detail = {}) => {
                    const event = new CustomEvent(eventName, {
                        detail,
                        bubbles: true,
                        composed: true,
                    })
                    window.dispatchEvent(event)
                }
            }

            if (prop === 'update') {
                return (propName, value) => {
                    if (state && typeof state.set === 'function') {
                        state.set(propName, value)
                    }
                    callActionViaProxy(el, state, '__updateProperty', { property: propName, value })
                }
            }

            if (actions && actions.has(prop)) {
                return (...args) => {
                    const params = parseActionArgs(args)
                    return callActionViaProxy(el, state, prop, params)
                }
            }

            if (state && typeof state.get === 'function') return state.get(prop)
            if (state && prop in state) return state[prop]
            if (state && state.proxy && prop in state.proxy) return state.proxy[prop]

            return undefined
        },

        set(target, prop, value) {
            if (prop === 'loading' || prop === 'get' || prop === 'refresh' || prop === 'dispatch' || prop === 'update') {
                return false
            }

            if (state && typeof state.set === 'function') {
                state.set(prop, value)
            } else if (state && state.proxy) {
                state.proxy[prop] = value
            } else if (state) {
                state[prop] = value
            }
            return true
        },
    })
}
