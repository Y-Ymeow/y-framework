// Scope Utils - 作用域工具函数
export function $dispatch(eventName, detail) {
    const event = new CustomEvent(eventName, {
        detail: detail || {},
        bubbles: true,
        composed: true
    });
    window.dispatchEvent(event);
}
