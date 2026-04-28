// Intl System - 国际化支持
import { initDirectives } from '../y-directive/directives.js';

let currentLocale = document.documentElement.lang || 'en';
let translations = {};
let localeCallbacks = [];

export function initIntl() {
    collectIntlElements();
    window.addEventListener('y:updated', () => {
        collectIntlElements();
    });
}

// 暴露 $locale 供指令系统使用
// 用法: $locale('zh') 或 $locale() 自动切换
window.$locale = function(locale) {
    if (!locale) {
        // 自动切换: zh <-> en
        locale = currentLocale === 'zh' ? 'en' : 'zh';
    }
    switchLocale(locale);
    return locale;
};

function collectIntlElements() {
    document.querySelectorAll('[data-intl]').forEach(el => {
        if (!el._y_intl_bound) {
            el._y_intl_bound = true;
        }
    });
}

export async function switchLocale(locale) {
    const keys = collectIntlKeys();
    if (keys.length === 0) return;

    try {
        const response = await fetch('/live/intl', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ keys, locale }),
        });

        const data = await response.json();
        if (!data.success) throw new Error('Intl request failed');

        currentLocale = data.locale;
        translations = data.translations;

        applyTranslations();
        document.documentElement.lang = currentLocale;

        window.dispatchEvent(new CustomEvent('y:locale-changed', {
            detail: { locale: currentLocale, translations }
        }));
    } catch (err) {
        console.error('Intl switch error:', err);
    }
}

function collectIntlKeys() {
    const keys = new Set();
    document.querySelectorAll('[data-intl]').forEach(el => {
        const key = el.dataset.intl;
        if (key) keys.add(key);
    });
    return Array.from(keys);
}

function applyTranslations() {
    document.querySelectorAll('[data-intl]').forEach(el => {
        const key = el.dataset.intl;
        if (key && translations[key] !== undefined) {
            el.textContent = translations[key];
        }
    });
}

export function getLocale() {
    return currentLocale;
}

export function getTranslation(key, fallback = null) {
    return translations[key] ?? fallback ?? key;
}
