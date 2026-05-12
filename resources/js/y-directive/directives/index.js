// Directives - 内置指令注册
import { evaluate } from "../evaluator/executor.js";
import { directiveContext } from "../reactive/context.js";

function directive(name, handler) {
    if (handler === undefined) {
        return directiveContext.registry.get(name);
    }
    directiveContext.registry.set(name, handler);
}

directive("text", (el, state, method, { content, effect, execute }) => {
    effect(() => {
        el.textContent = execute(content) ?? "";
    });
});

directive("html", (el, state, method, { content, effect, execute }) => {
    effect(() => {
        el.innerHTML = execute(content) ?? "";
    });
});

directive("show", (el, state, method, { content, effect, execute }) => {
    effect(() => {
        const visible = execute(content);
        el.style.display = visible ? "" : "none";
    });
});

directive("if", (el, state, method, { content, effect, execute }) => {
    const placeholder = document.createComment("y-if");
    let hasPlaceholder = false;

    effect(() => {
        const visible = !!execute(content);
        if (visible) {
            if (hasPlaceholder) {
                placeholder.parentNode?.replaceChild(el, placeholder);
                hasPlaceholder = false;
            }
        } else {
            if (!hasPlaceholder && el.parentNode) {
                el.parentNode.replaceChild(placeholder, el);
                hasPlaceholder = true;
            }
        }
    });
});

directive(
    "model",
    (el, state, method, { content, effect, execute, $execute, $evaluate }) => {
        const isCheckbox = el.type === "checkbox";
        const isContentEditable = el.contentEditable === "true";

        const handleInput = () => {
            const val = isContentEditable
                ? el.innerHTML
                : isCheckbox
                  ? el.checked
                  : el.value;
            $execute(`${content} = ${JSON.stringify(val)}`);
        };

        el.addEventListener("input", handleInput);
        if (!isContentEditable && !isCheckbox) {
            el.addEventListener("change", handleInput);
        }

        effect(() => {
            const val = execute(content);
            if (isContentEditable) {
                if (el.innerHTML !== val) el.innerHTML = val ?? "";
            } else if (isCheckbox) {
                el.checked = !!val;
            } else {
                if (el.value !== val) el.value = val ?? "";
            }
        });
    },
);

directive("bind", (el, state, method, { content, effect, execute }) => {
    const targetAttr = method;
    if (!targetAttr) return;

    if (targetAttr === "class") {
        const staticClass = el.getAttribute("class") || "";
        effect(() => {
            const val = execute(content);
            let dynamicClass = "";
            if (typeof val === "object" && val !== null) {
                dynamicClass = Object.entries(val)
                    .filter(([, v]) => v)
                    .map(([k]) => k)
                    .join(" ");
            } else {
                dynamicClass = String(val || "");
            }
            el.setAttribute("class", (staticClass + " " + dynamicClass).trim());
        });
    } else {
        effect(() => {
            const val = execute(content);
            if (val === false || val === null || val === undefined) {
                el.removeAttribute(targetAttr);
            } else {
                el.setAttribute(targetAttr, val === true ? "" : val);
            }
        });
    }
});

directive("on", (el, state, method, { content, modifiers, $execute }) => {
    const eventName = method;
    const handler = (e) => {
        if (modifiers.includes("prevent")) e.preventDefault();
        if (modifiers.includes("stop")) e.stopPropagation();

        $execute(content, state, e);
    };

    let target = el;
    if (modifiers.includes("window")) target = window;
    else if (modifiers.includes("document")) target = document;

    let wrappedHandler = handler;

    if (modifiers.includes("debounce")) {
        let timer;
        const delay =
            parseInt(modifiers[modifiers.indexOf("debounce") + 1]) || 300;
        wrappedHandler = (e) => {
            clearTimeout(timer);
            timer = setTimeout(() => handler(e), delay);
        };
    }

    if (modifiers.includes("throttle")) {
        let lastTime = 0;
        const delay =
            parseInt(modifiers[modifiers.indexOf("throttle") + 1]) || 300;
        wrappedHandler = (e) => {
            const now = Date.now();
            if (now - lastTime >= delay) {
                lastTime = now;
                handler(e);
            }
        };
    }

    if (modifiers.includes("outside")) {
        const outsideHandler = (e) => {
            if (!el.contains(e.target)) handler(e);
        };
        document.addEventListener(eventName || "click", outsideHandler);
        return () =>
            document.removeEventListener(eventName || "click", outsideHandler);
    }

    target.addEventListener(eventName, wrappedHandler);

    return () => {
        target.removeEventListener(eventName, wrappedHandler);
    };
});

directive("effect", (el, state, method, { content, effect, $execute }) => {
    effect(() => {
        $execute(content);
    });
});

directive("for", (el, state, method, { content, effect, execute }) => {
    const [itemName, listExpr] = content.split(" in ").map((s) => s.trim());
    const template = el.querySelector("template");

    if (!template) return;

    el._y_for_nodes = el._y_for_nodes || [];

    effect(() => {
        const list = execute(listExpr) || [];

        el._y_for_nodes.forEach((node) => node.remove());
        el._y_for_nodes = [];

        list.forEach((item, index) => {
            const clone = template.content.cloneNode(true);
            const children = Array.from(clone.children);

            children.forEach((child) => {
                child._y_local_scope = { [itemName]: item, $index: index };
                el._y_for_nodes.push(child);
            });

            el.appendChild(clone);
            children.forEach((child) => {
                if (window.Y?.initDirectives) {
                    window.Y.initDirectives(child);
                }
            });
        });
    });
});

directive(
    "init",
    (el, state, method, { content, effect, execute, $execute }) => {
        execute(content);
    },
);

directive(
    "ajax",
    (el, state, method, { content, cleanup }) => {
        if (!content || !method) return;

        const template = el.querySelector('template');
        if (!template) return;

        const templateHTML = template.innerHTML;
        const keyPath = el.getAttribute('data-ajax-key') || 'data';
        const container = el.querySelector('[data-ajax-container]') || el;
        let controller = null;

        const showState = (stateClass) => {
            el.classList.remove('y-ajax-loading', 'y-ajax-error', 'y-ajax-empty');
            if (stateClass) el.classList.add(stateClass);
        };

        const resolvePath = (obj, path) => {
            for (const key of path.split('.')) {
                if (obj == null) return null;
                obj = obj[key];
            }
            return obj;
        };

        const fetchAndRender = async () => {
            if (controller) controller.abort();
            controller = new AbortController();
            showState('y-ajax-loading');

            try {
                const res = await fetch(content, { signal: controller.signal });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const json = await res.json();

                let items = resolvePath(json, keyPath);
                if (!Array.isArray(items)) items = [];

                if (!items.length) {
                    showState('y-ajax-empty');
                    return;
                }

                container.querySelectorAll('[data-ajax-item]').forEach(n => n.remove());

                items.forEach(item => {
                    let html = templateHTML;
                    if (typeof item === 'object' && item !== null) {
                        for (const [key, value] of Object.entries(item)) {
                            html = html.replace(new RegExp(`\\{${key}\\}`, 'g'), String(value ?? ''));
                        }
                    }
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    Array.from(wrapper.children).forEach(child => {
                        child.setAttribute('data-ajax-item', '');
                        container.appendChild(child);
                    });
                });

                showState('y-ajax-loaded');

                container.querySelectorAll('[data-ajax-item]').forEach(child => {
                    if (window.Y?.initDirectives) {
                        window.Y.initDirectives(child);
                    }
                });
            } catch (e) {
                if (e.name === 'AbortError') return;
                showState('y-ajax-error');
                const errorEl = el.querySelector('[data-ajax-error]');
                if (errorEl) errorEl.textContent = e.message || '请求失败';
            }
        };

        const trigger = el.getAttribute('data-ajax-trigger') || 'load';

        if (trigger === 'load') {
            fetchAndRender();
        } else {
            const handler = () => fetchAndRender();
            el.addEventListener(trigger, handler);
            cleanup(() => el.removeEventListener(trigger, handler));
        }

        cleanup(() => {
            if (controller) controller.abort();
        });
    },
);
