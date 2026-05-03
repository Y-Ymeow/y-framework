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
    protected string $message = '';
    protected string $type = 'info';
    protected int $duration = 3000;
    protected bool $closeable = true;
    protected ?string $title = null;
    protected ?string $icon = null;
    protected string $position = 'top-right';

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
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-toast');
        $el->class("ux-toast-{$this->type}");
        $el->data('toast', 'true');
        $el->data('duration', (string) $this->duration);
        $el->data('position', $this->position);

        if ($this->closeable) {
            $el->data('closeable', 'true');
        }

        $iconMap = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];
        $icon = $this->icon ?? ($iconMap[$this->type] ?? 'ℹ');

        $el->child(Element::make('div')->class('ux-toast-icon')->html($icon));

        $contentEl = Element::make('div')->class('ux-toast-content');
        if ($this->title) {
            $contentEl->child(Element::make('div')->class('ux-toast-title')->text($this->title));
        }
        $contentEl->child(Element::make('div')->class('ux-toast-message')->text($this->message));
        $el->child($contentEl);

        if ($this->closeable) {
            $el->child(
                Element::make('button')
                    ->class('ux-toast-close')
                    ->data('toast-close', '')
                    ->html('&times;')
            );
        }

        return $el;
    }
}
