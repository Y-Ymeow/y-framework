<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 消息提示
 *
 * 用于显示轻量级消息提示（Toast），支持多种类型、时长、可关闭、标题、图标、位置。
 *
 * @ux-category Dialog
 * @ux-since 1.0.0
 * @ux-example Toast::make()->message('操作成功')->success()
 * @ux-example Toast::make()->message('警告')->warning()->duration(5000)
 * @ux-example Toast::make()->message('错误')->error()->title('提示')->position('top-center')
 * @ux-js-component toast.js
 * @ux-css toast.css
 */
class Toast extends UXComponent
{
    protected static ?string $componentName = 'toast';

    protected string $message = '';
    protected string $type = 'info';
    protected int $duration = 3000;
    protected bool $closeable = true;
    protected ?string $title = null;
    protected ?string $icon = null;
    protected string $position = 'top-right';

    protected function init(): void
    {
        $this->registerJs('toast', '
            const Toast = {
                show(detail) {
                    const container = document.getElementById("ux-toast-container") || this.createContainer();
                    const toast = document.createElement("div");
                    toast.className = `ux-toast ux-toast-${detail.type || "info"}`;
                    const icon = { success: "✓", error: "✕", warning: "!", info: "i" }[detail.type] || "i";
                    toast.innerHTML = `
                        <div class="ux-toast-icon">${icon}</div>
                        <div class="ux-toast-content">
                            ${detail.title ? `<div class="ux-toast-title">${detail.title}</div>` : ""}
                            <div class="ux-toast-message">${detail.message}</div>
                        </div>
                        <button class="ux-toast-close" data-ux-toast-close>&times;</button>
                    `;
                    container.appendChild(toast);
                    const close = () => {
                        toast.style.opacity = "0";
                        toast.style.transform = "translateX(20px)";
                        setTimeout(() => toast.remove(), 300);
                    };
                    toast.querySelector("[data-ux-toast-close]").onclick = close;
                    if (detail.duration !== 0) setTimeout(close, detail.duration || 3000);
                },
                init() {
                    window.addEventListener("toast:show", (e) => {
                        this.show(e.detail || {});
                    });
                },
                createContainer() {
                    const el = document.createElement("div");
                    el.id = "ux-toast-container";
                    el.className = "ux-toast-container top-right";
                    document.body.appendChild(el);
                    return el;
                }
            };
            return Toast;
        ');

        $this->registerCss(<<<'CSS'
.ux-toast-container {
    position: fixed;
    z-index: 1100;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    pointer-events: none;
}
.ux-toast-container.top-right {
    top: 0;
    right: 0;
}
.ux-toast-container.top-left {
    top: 0;
    left: 0;
}
.ux-toast-container.top-center {
    top: 0;
    left: 50%;
    transform: translateX(-50%);
}
.ux-toast-container.bottom-right {
    bottom: 0;
    right: 0;
}
.ux-toast-container.bottom-left {
    bottom: 0;
    left: 0;
}
.ux-toast-container.bottom-center {
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
}
.ux-toast {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    border: 1px solid #e5e7eb;
    min-width: 18rem;
    max-width: 24rem;
    pointer-events: auto;
    opacity: 0;
    transform: translateX(20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.ux-toast-container.top-left .ux-toast,
.ux-toast-container.bottom-left .ux-toast {
    transform: translateX(-20px);
}
.ux-toast {
    opacity: 1;
    transform: translateX(0);
}
.ux-toast-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    margin-top: 0.0625rem;
}
.ux-toast-success .ux-toast-icon { background: #22c55e; }
.ux-toast-error .ux-toast-icon { background: #ef4444; }
.ux-toast-warning .ux-toast-icon { background: #f59e0b; }
.ux-toast-info .ux-toast-icon { background: #3b82f6; }
.ux-toast-content {
    flex: 1;
    min-width: 0;
}
.ux-toast-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.125rem;
}
.ux-toast-message {
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.5;
}
.ux-toast-close {
    background: none;
    border: none;
    font-size: 1.125rem;
    line-height: 1;
    color: #9ca3af;
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: color 0.15s;
}
.ux-toast-close:hover {
    color: #374151;
}
CSS
        );
    }

    /**
     * 设置提示消息
     * @param string $message 消息内容
     * @return static
     * @ux-example Toast::make()->message('操作成功')
     */
    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置提示类型
     * @param string $type 类型：success/error/warning/info
     * @return static
     * @ux-example Toast::make()->type('success')
     * @ux-default 'info'
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 成功类型
     * @return static
     * @ux-example Toast::make()->success()
     */
    public function success(): static
    {
        return $this->type('success');
    }

    /**
     * 错误类型
     * @return static
     * @ux-example Toast::make()->error()
     */
    public function error(): static
    {
        return $this->type('error');
    }

    /**
     * 警告类型
     * @return static
     * @ux-example Toast::make()->warning()
     */
    public function warning(): static
    {
        return $this->type('warning');
    }

    /**
     * 信息类型
     * @return static
     * @ux-example Toast::make()->info()
     */
    public function info(): static
    {
        return $this->type('info');
    }

    /**
     * 设置自动关闭时长（毫秒）
     * @param int $ms 时长（毫秒）
     * @return static
     * @ux-example Toast::make()->duration(5000)
     * @ux-default 3000
     */
    public function duration(int $ms): static
    {
        $this->duration = $ms;
        return $this;
    }

    /**
     * 设置是否可手动关闭
     * @param bool $closeable 是否可关闭
     * @return static
     * @ux-example Toast::make()->closeable(false)
     * @ux-default true
     */
    public function closeable(bool $closeable = true): static
    {
        $this->closeable = $closeable;
        return $this;
    }

    /**
     * 设置标题
     * @param string $title 标题文字
     * @return static
     * @ux-example Toast::make()->title('提示')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置图标
     * @param string $icon 图标字符或 HTML
     * @return static
     * @ux-example Toast::make()->icon('✅')
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * 设置显示位置
     * @param string $position 位置：top-right/top-left/bottom-right/bottom-left
     * @return static
     * @ux-example Toast::make()->position('top-center')
     * @ux-default 'top-right'
     */
    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    /**
     * 右上角
     * @return static
     * @ux-example Toast::make()->topRight()
     */
    public function topRight(): static
    {
        return $this->position('top-right');
    }

    /**
     * 左上角
     * @return static
     * @ux-example Toast::make()->topLeft()
     */
    public function topLeft(): static
    {
        return $this->position('top-left');
    }

    /**
     * 右下角
     * @return static
     * @ux-example Toast::make()->bottomRight()
     */
    public function bottomRight(): static
    {
        return $this->position('bottom-right');
    }

    /**
     * 左下角
     * @return static
     * @ux-example Toast::make()->bottomLeft()
     */
    public function bottomLeft(): static
    {
        return $this->position('bottom-left');
    }

    /**
     * 生成执行脚本
     * @return string JavaScript 代码
     * @ux-example echo $toast->script()
     */
    public function script(): string
    {
        return "UX.toast.show('{$this->type}', '" . addslashes($this->message) . "', {$this->duration});";
    }

    /**
     * 生成 Toast 容器 HTML
     * @return string HTML 代码
     * @ux-example echo Toast::container()
     */
    public static function container(): string
    {
        return '<div class="ux-toast-container" id="ux-toast-container"></div>';
    }

    protected function toElement(): Element
    {
        // Toast 是纯 JS 驱动的组件，不需要输出可见 DOM
        // 使用注释模式占位，只注册 JS 资源
        $el = Element::make('div')->comment('ux-toast');
        $this->buildElement($el);
        return $el;
    }
}
