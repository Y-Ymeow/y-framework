// Sanitizer - 输入安全过滤
const BLOCKED_TAGS = new Set([
    'script', 'iframe', 'object', 'embed', 'link', 'meta', 'base', 'form'
]);

const BLOCKED_ATTRS = new Set([
    'onerror', 'onload', 'onclick', 'onmouseover', 'onfocus', 'onblur'
]);

export function sanitizeHtml(html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    sanitizeNode(template.content);
    return template.innerHTML;
}

function sanitizeNode(node) {
    const walker = document.createTreeWalker(node, NodeFilter.SHOW_ELEMENT);
    const nodes = [];

    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }

    nodes.forEach(el => {
        const tagName = el.tagName.toLowerCase();
        if (BLOCKED_TAGS.has(tagName)) {
            el.remove();
            return;
        }

        Array.from(el.attributes).forEach(attr => {
            const name = attr.name.toLowerCase();
            const value = attr.value;

            if (name.startsWith('on') || BLOCKED_ATTRS.has(name)) {
                el.removeAttribute(attr.name);
                return;
            }

            if ((name === 'href' || name === 'src') && /^javascript:/i.test(value)) {
                el.removeAttribute(attr.name);
            }
        });
    });
}

export function sanitizeAttr(name, value) {
    if (name.startsWith('on') || BLOCKED_ATTRS.has(name)) {
        return null;
    }
    if ((name === 'href' || name === 'src') && /^javascript:/i.test(value)) {
        return null;
    }
    return value;
}
