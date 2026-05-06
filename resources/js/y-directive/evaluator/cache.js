// Evaluator Cache - 表达式缓存
const MAX_CACHE_SIZE = 500;

export const exprCache = new Map();
export const execCache = new Map();

export function getCachedExpr(expr, cache = exprCache) {
    return cache.get(expr);
}

export function setCachedExpr(expr, fn, cache = exprCache) {
    if (cache.size >= MAX_CACHE_SIZE) {
        const firstKey = cache.keys().next().value;
        cache.delete(firstKey);
    }
    cache.set(expr, fn);
}

export function clearCaches() {
    exprCache.clear();
    execCache.clear();
}
