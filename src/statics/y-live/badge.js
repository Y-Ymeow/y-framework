// Live Badge - Live 组件标识
export function initBadge() {
    const style = document.createElement('style');
    style.textContent = `
        .live-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: live-pulse 2s infinite;
            pointer-events: none;
            z-index: 9999;
        }
        @keyframes live-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        [data-live] {
            position: relative;
        }
    `;
    document.head.appendChild(style);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.hasAttribute('data-live') && !node.querySelector('.live-badge')) {
                        addBadge(node);
                    }
                    node.querySelectorAll?.('[data-live]')?.forEach(el => {
                        if (!el.querySelector('.live-badge')) addBadge(el);
                    });
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    document.querySelectorAll('[data-live]').forEach(el => {
        if (!el.querySelector('.live-badge')) addBadge(el);
    });
}

function addBadge(el) {
    const badge = document.createElement('span');
    badge.className = 'live-badge';
    badge.title = 'Live Component';
    el.style.position = 'relative';
    el.appendChild(badge);
}
