import { dispatchAction, dispatchState, getComponentInfo, updateLiveStateAttr } from './connection.js'
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
        const result = await dispatchAction(el, componentClass, action, { value: info.__state }, state, null, params)

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
    // Track local draft changes (deferred sync)
    if (!el._y_live_drafts) {
        el._y_live_drafts = {};
    }

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
                return async (propName, value) => {
                    if (state && typeof state.set === 'function') {
                        state.set(propName, value)
                    }
                    // Use the lightweight /live/state endpoint for property updates
                    await dispatchStateUpdate(el, state, { property: propName, value })
                }
            }

            if (prop === 'setDraft') {
                return (key, value) => {
                    // Store locally, do NOT roundtrip
                    el._y_live_drafts[key] = value;
                    if (state && typeof state.set === 'function') {
                        state.set(key, value)
                    }
                }
            }

            if (prop === 'commitDraft') {
                return async () => {
                    const drafts = { ...el._y_live_drafts };
                    el._y_live_drafts = {};
                    if (Object.keys(drafts).length === 0) return;

                    // Batch all draft properties to the state endpoint
                    const info = getComponentInfo(el)
                    const componentClass = info.__component
                    if (!componentClass) return;

                    showLiveProgress()
                    try {
                        const result = await dispatchState(el, componentClass, { value: info.__state }, state)
                        if (result && result.success) {
                            const data = result.data
                            updateLiveStateAttr(el, data.state, data.patches)
                            if (data.patches && state && typeof state.merge === 'function') {
                                batch(() => { state.merge(data.patches) })
                            }
                        }
                    } catch (err) {
                        console.error('[y-live] commitDraft error:', err)
                    } finally {
                        hideLiveProgress()
                    }
                }
            }

            // $live.$parent — returns a proxy targeting the parent component
            if (prop === '$parent') {
                const parentLiveEl = el.parentElement?.closest('[data-live]')
                if (!parentLiveEl || parentLiveEl === el) return undefined

                const parentInfo = getComponentInfo(parentLiveEl)
                const parentActions = new Set(parentInfo.__actions || [])
                const parentState = parentLiveEl._y_state

                return new Proxy({}, {
                    get(_, parentProp) {
                        if (parentProp === 'get') {
                            return () => parentState ? parentState.all() : {}
                        }
                        if (parentProp === 'refresh') {
                            return (name) => dispatchRefresh(parentLiveEl, parentState, name)
                        }
                        if (parentActions.has(parentProp)) {
                            return (...args) => {
                                const params = parseActionArgs(args)
                                return callActionViaProxy(parentLiveEl, parentState, parentProp, params)
                            }
                        }
                        if (parentState && typeof parentState.get === 'function') return parentState.get(parentProp)
                        if (parentState && parentProp in parentState) return parentState[parentProp]
                        return undefined
                    }
                })
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
            if (prop === 'loading' || prop === 'get' || prop === 'refresh' || prop === 'dispatch' || prop === 'update'
                || prop === 'setDraft' || prop === 'commitDraft' || prop === '$parent' || prop === 'drafts') {
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

/**
 * Dispatch a state update via the lightweight /live/state endpoint.
 */
async function dispatchStateUpdate(el, state, params) {
    const info = getComponentInfo(el)
    const componentClass = info.__component
    if (!componentClass) return

    showLiveProgress()
    try {
        const result = await dispatchState(el, componentClass, { value: info.__state }, state)
        if (result && result.success) {
            const data = result.data
            if (data.state) updateLiveStateAttr(el, data.state, data.patches)
            if (data.patches && state && typeof state.merge === 'function') {
                batch(() => { state.merge(data.patches) })
            }
        }
    } catch (err) {
        console.error('[y-live] State update error:', err)
    } finally {
        hideLiveProgress()
    }
}
