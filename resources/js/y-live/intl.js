import { initDirectives } from '../y-directive/directives.js';

let currentLocale = document.documentElement.lang || 'en';
let translations = {};

export function initIntl() {
    collectIntlElements();
    window.addEventListener('y:updated', () => {
        collectIntlElements();
    });
}

window.$locale = function(locale) {
    if (!locale) {
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

        document.cookie = 'locale=' + locale + ';path=/;max-age=31536000;samesite=Lax';
        document.documentElement.lang = currentLocale;

        applyTranslations();

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
        let key = el.dataset.intl;
        if (el.dataset.intlParams) {
            key = {
                key,
                params: el.dataset.intlParams,
            };
        }
        if (key) keys.add(key);
    });
    return Array.from(keys);
}



function applyTranslations() {
    document.querySelectorAll('[data-intl]').forEach(el => {
        let key = el.dataset.intl;
        if (el.dataset.intlParams) {
            key = key + ' ' + el.dataset.intlParams;
        }

        if (el.dataset.intlAttr) {
            el.setAttribute(el.dataset.intlAttr, translations[key]);
            return;
        }

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
