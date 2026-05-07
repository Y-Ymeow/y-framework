import { ReactiveState, batch } from '../../y-directive/reactive/index.js'
import { getComponentInfo, updateLiveStateAttr } from './connection.js'
import { createLiveProxy } from './live-proxy.js'
import { registerScopeVar } from '../../y-directive/scope/scope.js'

export function setupLiveComponent(el, onAction, target = null) {
    if (!el._y_live_ready) {
        el._y_live_ready = true

        const info = getComponentInfo(el)
        const props = info && info.__props ? info.__props : {}

        const state = new ReactiveState(props)
        el._y_state = state

        el._y_live_component_class = info.__component || el.dataset.live || ''
        el._y_live_state_ref = { value: info.__state || '' }

        el._liveDispatch = (action, e, params = {}) => {
            if (onAction) {
                onAction(el, el._y_live_component_class, action, el._y_live_state_ref, el._y_state, e, params)
            }
        }

        const actionSet = new Set(info.__actions || [])
        el.$live = createLiveProxy(el, state, actionSet)
        registerScopeVar(el, '$live', el.$live)
    }
}

export function applyLiveResponse(el, data, state, stateRef) {
    if (data.state) {
        updateLiveStateAttr(el, data.state, data.patches)
        if (stateRef) stateRef.value = data.state
    }

    if (data.patches && state && typeof state.merge === 'function') {
        batch(() => {
            state.merge(data.patches)
        })
    }

    return data.operations || []
}
