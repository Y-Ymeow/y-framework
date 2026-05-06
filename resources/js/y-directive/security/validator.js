// Validator - 表达式验证器
import { FORBIDDEN_PATTERNS, MAX_EXPR_LENGTH } from './patterns.js';

export function validateExpression(expr) {
    if (!expr || typeof expr !== 'string') {
        return { valid: false, error: 'Invalid expression' };
    }

    if (expr.length > MAX_EXPR_LENGTH) {
        return { valid: false, error: 'Expression too long' };
    }

    for (const pattern of FORBIDDEN_PATTERNS) {
        if (pattern.test(expr)) {
            return { valid: false, error: `Forbidden pattern detected: ${pattern}` };
        }
    }

    return { valid: true };
}

export function validateForExecution(expr) {
    const result = validateExpression(expr);
    if (!result.valid) {
        console.warn(`[y-directive] Security: ${result.error}`, expr);
        return false;
    }
    return true;
}
