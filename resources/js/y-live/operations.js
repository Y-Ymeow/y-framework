// Operations System - Live 操作执行
import {
    createSafeFragment,
    replaceLiveHtml,
    applyLiveFragment,
} from "./core/dom.js";
import { bindNavigateLinks, navigate as clientNavigate } from "./navigate.js";

function loadExternalScript(src, id = null) {
    if (!src) return;

    const key = id || src;
    const exists = Array.from(
        document.querySelectorAll("script[data-live-script]"),
    ).some((script) => script.dataset.liveScript === key);
    if (exists) return;

    const script = document.createElement("script");
    script.src = src;
    script.defer = true;
    script.dataset.liveScript = key;
    document.body.appendChild(script);
}

export function executeOperation(op) {
    if (op.op.startsWith("ux:")) {
        console.log("has ux");
    }

    switch (op.op) {
        case "update": {
            const val = String(op.value ?? "");
            let input =
                document.querySelector(
                    `input[name="${op.target}"], textarea[name="${op.target}"], select[name="${op.target}"]`,
                ) || document.getElementById(op.target);
            if (input) {
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
            break;
        }
        case "html": {
            const el = document.querySelector(op.selector);
            if (el) replaceLiveHtml(el, op.html);
            break;
        }
        case "domPatch": {
            const el = document.querySelector(op.selector);
            if (el) replaceLiveHtml(el, op.html);
            break;
        }
        case "append": {
            const el = document.querySelector(op.selector);
            if (el) el.appendChild(createSafeFragment(op.html));
            break;
        }
        case "remove": {
            const el = document.querySelector(op.selector);
            if (el) el.remove();
            break;
        }
        case "addClass": {
            const el = document.querySelector(op.selector);
            if (el) el.classList.add(...op.class.split(" "));
            break;
        }
        case "removeClass": {
            const el = document.querySelector(op.selector);
            if (el) el.classList.remove(...op.class.split(" "));
            break;
        }
        case "openModal": {
            const m = document.querySelector(`[data-ux-modal="${op.id}"]`);
            if (m) {
                m.setAttribute("data-visible", "");
                document.body.style.overflow = "hidden";
            }
            break;
        }
        case "closeModal": {
            const m = document.querySelector(`[data-ux-modal="${op.id}"]`);
            if (m) {
                m.removeAttribute("data-visible");
                document.body.style.overflow = "";
            }
            break;
        }
        case "navigate":
            clientNavigate(op.url, {
                replace: op.replace === true,
                fragment: op.fragment || null,
                state: op.state || null,
            });
            break;
        case "redirect":
            if (document.startViewTransition) {
                document.startViewTransition(() => {
                    window.location.href = op.url;
                });
            } else {
                window.location.href = op.url;
            }
            break;
        case "reload":
            window.location.reload();
            break;
        case "js":
            console.warn("Live js operation is disabled for security reasons.");
            break;
        case "loadScript":
            loadExternalScript(op.src, op.id || null);
            break;
        case "dispatch":
            window.dispatchEvent(
                new CustomEvent(op.event, { detail: op.detail || {} }),
            );
            break;
        default:
            if (op.op && op.op.startsWith("ux:")) {
                console.warn(
                    `[y-live] ux: operation "${op.op}" not handled by operations.js, ensure UX hookLive is active`,
                );
            }
            break;
    }
}

export function executeOperations(operations) {
    if (!Array.isArray(operations)) return;
    operations.forEach((op) => executeOperation(op));
}
