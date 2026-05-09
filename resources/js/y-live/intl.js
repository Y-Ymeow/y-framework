import { initDirectives } from "../y-directive";

let currentLocale = document.documentElement.lang || "en";
let translations = {};

export function initIntl() {
    collectIntlElements();
    window.addEventListener("y:updated", () => {
        collectIntlElements();
    });
}

window.$locale = function (locale) {
    if (!locale) {
        locale = currentLocale === "zh" ? "en" : "zh";
    }
    switchLocale(locale);
    return locale;
};

function collectIntlElements() {
    document.querySelectorAll("[data-intl]").forEach((el) => {
        if (!el._y_intl_bound) {
            el._y_intl_bound = true;
            el._y_intl_original = el.dataset.intlAttr
                ? el.getAttribute(el.dataset.intlAttr)
                : el.textContent;
        }
    });
}

export async function switchLocale(locale) {
    const keys = collectIntlKeys();

    if (keys.length === 0) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    try {
        const response = await fetch("/live/intl", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-Token": csrfToken,
            },
            body: JSON.stringify({ keys, locale }),
        });

        const data = await response.json();
        if (!data.success) throw new Error("Intl request failed");

        currentLocale = data.locale;
        translations = data.translations;

        document.cookie =
            "locale=" + locale + ";path=/;max-age=31536000;samesite=Lax";
        document.documentElement.lang = currentLocale;

        applyTranslations();

        window.dispatchEvent(
            new CustomEvent("y:locale-changed", {
                detail: { locale: currentLocale, translations },
            }),
        );
    } catch (err) {
        console.error("Intl switch error:", err);
    }
}

function collectIntlKeys() {
    const seen = new Set();
    const keys = [];
    document.querySelectorAll("[data-intl]").forEach((el) => {
        let key = el.dataset.intl;
        let entry;
        if (el.dataset.intlParams) {
            entry = {
                key,
                params: el.dataset.intlParams,
            };
        } else {
            entry = key;
        }
        let entryKey =
            typeof entry === "object" ? entry.key + " " + entry.params : entry;
        if (!seen.has(entryKey)) {
            seen.add(entryKey);
            keys.push(entry);
        }
    });
    return keys;
}

function applyTranslations() {
    document.querySelectorAll("[data-intl]").forEach((el) => {
        let key = el.dataset.intl;
        let lookupKey = key;
        if (el.dataset.intlParams) {
            lookupKey = key + " " + el.dataset.intlParams;
        }

        let translated = translations[lookupKey];

        if (translated === undefined || translated === key) {
            if (el._y_intl_original !== undefined) {
                translated = el._y_intl_original;
            }
        }

        if (el.dataset.intlAttr) {
            if (translated !== undefined) {
                el.setAttribute(el.dataset.intlAttr, translated);
            }
            return;
        }

        if (translated !== undefined) {
            el.textContent = translated;
        }
    });
}

export function getLocale() {
    return currentLocale;
}

export function getTranslation(key, fallback = null) {
    return translations[key] ?? fallback ?? key;
}
