<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 提示框
 *
 * 用于显示重要提示信息，支持多种颜色变体、可关闭、带标题和图标。
 *
 * @ux-category Feedback
 * @ux-since 1.0.0
 * @ux-example Alert::make()->message('操作成功')->success()
 * @ux-example Alert::make()->message('警告信息')->warning()->dismissible()
 * @ux-js-component —
 * @ux-css alert.css
 */
class Alert extends UXComponent
{
    protected string $message = '';
    protected string $type = 'info';
    protected bool $dismissible = false;
    protected ?string $title = null;
    protected ?string $icon = null;

    /**
     * 设置提示消息
     * @param string $message 消息内容
     * @return static
     * @ux-example Alert::make()->message('操作成功')
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
     * @ux-example Alert::make()->type('success')
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
     * @ux-example Alert::make()->success()
     */
    public function success(): static
    {
        return $this->type('success');
    }

    /**
     * 错误类型
     * @return static
     * @ux-example Alert::make()->error()
     */
    public function error(): static
    {
        return $this->type('error');
    }

    /**
     * 警告类型
     * @return static
     * @ux-example Alert::make()->warning()
     */
    public function warning(): static
    {
        return $this->type('warning');
    }

    /**
     * 信息类型
     * @return static
     * @ux-example Alert::make()->info()
     */
    public function info(): static
    {
        return $this->type('info');
    }

    /**
     * 设置是否可关闭
     * @param bool $dismissible 是否可关闭
     * @return static
     * @ux-example Alert::make()->dismissible()
     * @ux-default true
     */
    public function dismissible(bool $dismissible = true): static
    {
        $this->dismissible = $dismissible;
        return $this;
    }

    /**
     * 设置标题
     * @param string $title 标题文字
     * @return static
     * @ux-example Alert::make()->title('提示')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置图标
     * @param string $icon 图标字符
     * @return static
     * @ux-example Alert::make()->icon('⚠')
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-alert');
        $el->class("ux-alert-{$this->type}");
        if ($this->dismissible) {
            $el->class('ux-alert-dismissible');
        }
        $el->attr('role', 'alert');

        $iconMap = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];

        $icon = $this->icon ?? ($iconMap[$this->type] ?? null);
        if ($icon) {
            $el->child(Element::make('span')->class('ux-alert-icon')->html($icon));
        }

        $contentEl = Element::make('div')->class('ux-alert-content');

        if ($this->title) {
            $contentEl->child(Element::make('div')->class('ux-alert-title')->text($this->title));
        }

        $contentEl->child(Element::make('div')->class('ux-alert-message')->html($this->message));
        $el->child($contentEl);

        if ($this->dismissible) {
            $el->child(
                Element::make('button')
                    ->attr('type', 'button')
                    ->class('ux-alert-close')
                    ->data('alert-close', '')
                    ->html('&times;')
            );
        }

        return $el;
    }
}
