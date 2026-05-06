// Security Patterns - 安全黑名单
export const FORBIDDEN_PATTERNS = [
    /\beval\b/,
    /\bFunction\b/,
    /\bwindow\.location\s*=/,
    /\b__proto__\b/,
    /\bconstructor\b/,
    /\bprototype\b/,
    /\bdocument\.cookie\b/,
    /\bdocument\.write\b/,
    /\bsetTimeout\s*\(\s*['"]/,
    /\bsetInterval\s*\(\s*['"]/,
];

export const MAX_EXPR_LENGTH = 500;
