export const Toast = {
    show(detail) {
        const container = document.getElementById('ux-toast-container') || this.createContainer();
        const toast = document.createElement('div');
        toast.className = `ux-toast ux-toast-${detail.type || 'info'}`;
        
        const icon = { success: '✓', error: '✕', warning: '!', info: 'i' }[detail.type] || 'i';
        toast.innerHTML = `
            <div class="ux-toast-icon">${icon}</div>
            <div class="ux-toast-content">
                ${detail.title ? `<div class="ux-toast-title">${detail.title}</div>` : ''}
                <div class="ux-toast-message">${detail.message}</div>
            </div>
            <button class="ux-toast-close" data-ux-toast-close>&times;</button>
        `;
        
        container.appendChild(toast);
        
        const close = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(() => toast.remove(), 300);
        };
        
        toast.querySelector('[data-ux-toast-close]').onclick = close;
        if (detail.duration !== 0) setTimeout(close, detail.duration || 3000);
    },
    
    createContainer() {
        const el = document.createElement('div');
        el.id = 'ux-toast-container';
        el.className = 'ux-toast-container top-right';
        document.body.appendChild(el);
        return el;
    }
};

export default Toast;
