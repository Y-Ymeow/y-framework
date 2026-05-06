// Directives Matcher - 指令匹配 + 解析
import { DIRECTIVE_PRIORITY } from './registry.js';

export function matchDirectives(el) {
    const matches = [];

    if (!el || !el.attributes) return matches;

    Array.from(el.attributes).forEach(attr => {
        if (!attr.name.startsWith('data-')) return;

        const fullName = attr.name.slice(5);
        let directiveName = '';
        let method = '';
        let modifiers = [];

        const colonIdx = fullName.indexOf(':');
        if (colonIdx > -1) {
            directiveName = fullName.slice(0, colonIdx);
            const rest = fullName.slice(colonIdx + 1);
            const parts = rest.split('.');
            method = parts[0];
            modifiers = parts.slice(1);
        } else {
            const parts = fullName.split('.');
            directiveName = parts[0];
            modifiers = parts.slice(1);
        }

        matches.push({
            type: directiveName,
            method: method,
            args: modifiers, // args 和 modifiers 在这种语境下通常相同
            modifiers: modifiers,
            content: attr.value,
            name: attr.name,
            priority: DIRECTIVE_PRIORITY[directiveName] || 50
        });
    });

    matches.sort((a, b) => a.priority - b.priority);

    return matches;
}
