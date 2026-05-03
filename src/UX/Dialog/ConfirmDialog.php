<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;

/**
 * 确认对话框组件
 *
 * 用于在执行敏感操作前显示确认对话框，如删除、提交等。
 * 支持自定义标题、消息、按钮文字和变体颜色。
 *
 * @ux-category Dialog
 * @ux-since 1.0.0
 * @ux-example ConfirmDialog::make()->id('delete-confirm')
 * @ux-example ConfirmDialog::make()->id('delete')->title('删除确认')->message('此操作不可撤销')->okVariant('danger')
 * @ux-example ConfirmDialog::make()->id('publish')->okText('发布')->cancelText('稍后')
 * @ux-live-support open, close
 * @ux-js-component confirm-dialog.js
 * @ux-css confirm-dialog.css
 * @ux-value-type string
 */
/**
 * 确认对话框
 *
 * 用于显示确认对话框，支持自定义标题、消息、按钮文字、颜色变体。
 *
 * @ux-category Dialog
 * @ux-since 1.0.0
 * @ux-example ConfirmDialog::make()->title('确认删除')->message('确定要删除吗？')->okText('删除')->okVariant('danger')
 * @ux-js-component confirm-dialog.js
 * @ux-css confirm-dialog.css
 */
class ConfirmDialog extends UXComponent
{
    protected string $title = '确认';
    protected string $message = '确定要执行此操作吗？';
    protected string $okText = '确定';
    protected string $cancelText = '取消';
    protected string $okVariant = 'danger';
    protected string $cancelVariant = 'secondary';
    protected bool $open = false;

    /**
     * 设置对话框标题
     * @param string $title 标题内容
     * @return static
     * @ux-example ConfirmDialog::make()->title('删除确认')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置确认消息
     * @param string $message 消息内容
     * @return static
     * @ux-example ConfirmDialog::make()->message('确定要删除此项吗？此操作不可撤销。')
     */
    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置确定按钮文字
     * @param string $text 按钮文字
     * @return static
     * @ux-example ConfirmDialog::make()->okText('确定删除')
     */
    public function okText(string $text): static
    {
        $this->okText = $text;
        return $this;
    }

    /**
     * 设置取消按钮文字
     * @param string $text 按钮文字
     * @return static
     * @ux-example ConfirmDialog::make()->cancelText('我再想想')
     */
    public function cancelText(string $text): static
    {
        $this->cancelText = $text;
        return $this;
    }

    /**
     * 设置确定按钮颜色变体
     * @param string $variant 变体名：primary/danger/success 等
     * @return static
     * @ux-example ConfirmDialog::make()->okVariant('danger')
     * @ux-default 'danger'
     */
    public function okVariant(string $variant): static
    {
        $this->okVariant = $variant;
        return $this;
    }

    /**
     * 设置取消按钮颜色变体
     * @param string $variant 变体名：primary/secondary 等
     * @return static
     * @ux-example ConfirmDialog::make()->cancelVariant('secondary')
     * @ux-default 'secondary'
     */
    public function cancelVariant(string $variant): static
    {
        $this->cancelVariant = $variant;
        return $this;
    }

    /**
     * 设置对话框打开状态
     * @param bool $open 是否打开
     * @return static
     * @ux-example ConfirmDialog::make()->open(true)
     * @ux-default false
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * 关闭对话框
     * @return static
     * @ux-example ConfirmDialog::make()->id('delete')->close()
     */
    public function close(): static
    {
        return $this->open(false);
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);

        $wrapper->class('ux-confirm-dialog');
        $wrapper->data('ux-confirm-id', $this->id);
        if ($this->open) {
            $wrapper->class('ux-confirm-dialog-open');
        }

        $backdrop = Element::make('div')
            ->class('ux-confirm-backdrop')
            ->data('ux-confirm-close', $this->id);
        $wrapper->child($backdrop);

        $dialog = Element::make('div')->class('ux-confirm-content');

        if ($this->title) {
            $dialog->child(
                Element::make('h3')->class('ux-confirm-title')->text($this->title)
            );
        }

        $dialog->child(
            Element::make('p')->class('ux-confirm-message')->text($this->message)
        );

        $footer = Element::make('div')->class('ux-confirm-footer');

        $cancelBtn = Button::make()
            ->label($this->cancelText)
            ->variant($this->cancelVariant)
            ->data('ux-confirm-close', $this->id);
        $footer->child($cancelBtn);

        $okBtn = Button::make()
            ->label($this->okText)
            ->variant($this->okVariant)
            ->data('ux-confirm-action', 'ok')
            ->data('ux-confirm-id', $this->id);
        $footer->child($okBtn);

        $dialog->child($footer);
        $wrapper->child($dialog);

        return $wrapper;
    }
}
