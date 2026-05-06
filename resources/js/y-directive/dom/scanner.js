// DOM Scanner - 扫描指令元素
import { directiveContext } from '../reactive/index.js';

export function scanDirectiveElements(root) {
    const elements = [];

    if (!root || !root.querySelectorAll) return elements;

    const allElements = root.querySelectorAll('*');
    const els = root.tagName ? [root, ...allElements] : allElements;

    els.forEach(el => {
        if (isDirectiveElement(el)) {
            elements.push(el);
        }
    });

    return elements;
}

export function isDirectiveElement(el) {
    if (!el || !el.attributes) return false;

    const attrs = el.attributes;
    for (let i = 0; i < attrs.length; i++) {
        const name = attrs[i].name;
        if (isDirectiveAttr(name)) {
            return true;
        }
    }
    return false;
}

export function isDirectiveAttr(name) {
    return name === 'data-text' ||
           name === 'data-html' ||
           name === 'data-show' ||
           name === 'data-if' ||
           name === 'data-for' ||
           name === 'data-model' ||
           name === 'data-effect' ||
           name.startsWith('data-on:') ||
           name.startsWith('data-bind:');
}

export function isElementBound(el) {
    return directiveContext.boundElements.has(el);
}

export function markElementBound(el) {
    directiveContext.boundElements.add(el);
}
