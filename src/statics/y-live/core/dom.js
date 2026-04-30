// Live DOM Operations - DOM 更新操作
const LIVE_BLOCKED_TAGS = new Set([
    'script', 'iframe', 'object', 'embed', 'link', 'meta', 'base'
]);

export function createSafeFragment(html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    sanitizeLiveTree(template.content);
    return template.content;
}

export function replaceLiveHtml(target, html, liveState = null, onAction = null) {
    target.replaceChildren(createSafeFragment(html));
}

export function sanitizeLiveTree(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
    const nodes = [];

    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }

    nodes.forEach((node) => {
        const tagName = node.tagName.toLowerCase();
        if (LIVE_BLOCKED_TAGS.has(tagName)) {
            node.remove();
            return;
        }

        Array.from(node.attributes).forEach((attr) => {
            const name = attr.name.toLowerCase();
            const value = attr.value ?? '';

            if (name.startsWith('on')) {
                node.removeAttribute(attr.name);
                return;
            }

            if (name.startsWith('data-') && !window.Y_UI_SAFE_ATTRS?.has(name)) {
                node.removeAttribute(attr.name);
                return;
            }

            if ((name === 'href' || name === 'src' || name === 'action' || name === 'formaction') && isUnsafeUrl(value)) {
                node.removeAttribute(attr.name);
            }
        });
    });
}

function isUnsafeUrl(value) {
    return /^\s*javascript:/i.test(value);
}

export function applyLiveFragment(liveEl, fragment, liveState = null) {
    if (!liveEl || !fragment?.name) return;

    const escapedName = CSS.escape(fragment.name);
    const selector = `[data-live-fragment="${escapedName}"]`;
    const target = liveEl.matches(selector) ? liveEl : liveEl.querySelector(selector);

    if (!target) return;

    const mode = fragment.mode || 'replace';

    if (mode === 'append') {
        target.appendChild(createSafeFragment(fragment.html || ''));
        return;
    }

    if (mode === 'prepend') {
        target.insertBefore(createSafeFragment(fragment.html || ''), target.firstChild);
        return;
    }

    replaceLiveHtml(target, fragment.html || '', liveState);
}
