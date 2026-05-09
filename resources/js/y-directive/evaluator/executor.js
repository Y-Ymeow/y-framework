// Evaluator Executor - 执行器
import { validateForExecution } from "../security/index.js";
import { getFullScope, getRootState } from "../scope/index.js";
import { getCachedExpr, setCachedExpr, execCache } from "./cache.js";

export function evaluate(expr, state, el) {
    if (!validateForExecution(expr)) {
        console.warn(`[y-directive] Blocked expression: ${expr}`);
        return undefined;
    }

    try {
        let fn = getCachedExpr(expr);
        if (!fn) {
            // 使用 return 确保返回值
            fn = new Function(
                "$",
                "$dispatch",
                "$el",
                "$root",
                `with($) { return (${expr}) }`,
            );
            setCachedExpr(expr, fn);
        }

        const scope = getFullScope(el, state);
        const rootState = getRootState(el) || (state && state.proxy) || state;

        return fn(scope, window.$dispatch, el, rootState);
    } catch (e) {
        console.warn(`[y-directive] Evaluate error: "${expr}"`, e);
        return undefined;
    }
}

export function execute(expr, state, event, el) {
    if (!validateForExecution(expr)) {
        console.warn(`[y-directive] Blocked expression: ${expr}`);
        return;
    }

    try {
        let fn = getCachedExpr(expr, execCache);
        if (!fn) {
            // 执行模式不需要 return
            fn = new Function(
                "$",
                "$event",
                "$dispatch",
                "$el",
                "$root",
                `with($) { ${expr} }`,
            );
            setCachedExpr(expr, fn, execCache);
        }

        const scope = getFullScope(el, state);
        const rootState = getRootState(el) || (state && state.proxy) || state;

        fn(scope, event, window.$dispatch, el, rootState);
    } catch (e) {
        console.warn(`[y-directive] Execute error: "${expr}"`, e);
    }
}
