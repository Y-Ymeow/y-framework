<?php

declare(strict_types=1);

namespace Framework\UX\Dialog;

use Framework\UX\UXComponent;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;

/**
 * Modal 弹窗组件
 *
 * 模态对话框，支持多种尺寸、自定义底部、遮罩层点击关闭。
 *
 * ## JS 交互能力（modal.js）
 *
 * PHP 定义结构 → JS 自动处理 open/close 动画和事件：
 *
 * - `Modal.open(id)` — 打开弹窗（添加 .ux-modal-open 类）
 * - `Modal.close(id)` — 关闭弹窗
 * - `Modal.init()` — 初始化事件监听（自动调用）
 *
 * ### 触发方式
 * 1. **通过 data 属性**: 按钮 `data-ux-modal-open="modal-id"` 点击打开
 * 2. **通过 PHP 方法**: `$modal->open(true)` 直接渲染为打开状态
 * 3. **通过 trigger()**: 生成带触发器的按钮 HTML
 *
 * ### 事件
 * - 打开时: JS 添加 `ux-modal-open` 类，设置 `data-visible="true"`
 * - 关闭时: 移除类和属性，恢复 body overflow
 * - 遮罩点击: 自动触发 close
 *
 * @ux-category Dialog
 * @ux-since 1.0.0
 *
 * @ux-example
 * // 基础用法
 * Modal::make()
 *     ->title('确认操作')
 *     ->content('确定要执行此操作吗？')
 *     ->footer(
 *         Button::make()->label('取消')->variant('secondary'),
 *         Button::make()->label('确定')->primary()->dispatch('confirm')
 *     );
 *
 * // 通过按钮触发
 * echo $modal->trigger('打开弹窗');
 * @ux-example-end
 */
class Modal extends UXComponent
{
    protected string $title = '';
    protected string $content = '';
    protected string $size = 'md';
    protected bool $closeable = true;
    protected bool $backdrop = true;
    protected bool $centered = true;
    protected mixed $footer = null;
    protected bool $open = false;

    /**
     * 设置弹窗标题
     * @param string $title 标题文字
     * @return static
     * @ux-example Modal::make()->title('确认操作')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置弹窗内容
     * @param mixed $content 内容（字符串/Closure/组件）
     * @return static
     * @ux-example Modal::make()->content('确定要执行此操作吗？')
     */
    public function content(mixed $content): static
    {
        if (is_string($content)) {
            $this->content = (string) $content;    
        } elseif ($content instanceof \Closure) {
            $this->content = $content();
        } else {
            $this->content = $content;
        }
        return $this;
    }

    /**
     * 设置弹窗尺寸
     * @param string $size 尺寸：sm/md/lg/xl/fullscreen
     * @return static
     * @ux-example Modal::make()->size('lg')
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 小尺寸
     * @return static
     * @ux-example Modal::make()->sm()
     */
    public function sm(): static { return $this->size('sm'); }

    /**
     * 大尺寸
     * @return static
     * @ux-example Modal::make()->lg()
     */
    public function lg(): static { return $this->size('lg'); }

    /**
     * 超大尺寸
     * @return static
     * @ux-example Modal::make()->xl()
     */
    public function xl(): static { return $this->size('xl'); }

    /**
     * 全屏尺寸
     * @return static
     * @ux-example Modal::make()->fullscreen()
     */
    public function fullscreen(): static { return $this->size('fullscreen'); }

    /**
     * 设置是否可关闭（显示关闭按钮）
     * @param bool $closeable 是否可关闭
     * @return static
     * @ux-example Modal::make()->closeable(false)
     * @ux-default true
     */
    public function closeable(bool $closeable = true): static
    {
        $this->closeable = $closeable;
        return $this;
    }

    /**
     * 设置是否显示遮罩层
     * @param bool $backdrop 是否显示遮罩
     * @return static
     * @ux-example Modal::make()->backdrop(true)
     * @ux-default true
     */
    public function backdrop(bool $backdrop = true): static
    {
        $this->backdrop = $backdrop;
        return $this;
    }

    /**
     * 设置是否居中显示
     * @param bool $centered 是否居中
     * @return static
     * @ux-example Modal::make()->centered(true)
     * @ux-default true
     */
    public function centered(bool $centered = true): static
    {
        $this->centered = $centered;
        return $this;
    }

    /**
     * 设置底部内容
     * @param mixed $footer 底部内容（字符串/数组/组件）
     * @return static
     * @ux-example Modal::make()->footer([Button::make()->label('取消')])
     */
    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * 设置打开状态
     * @param bool $open 是否打开
     * @return static
     * @ux-example Modal::make()->open(true)
     * @ux-default false
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * 关闭弹窗
     * @return static
     * @ux-example $modal->close()
     */
    public function close(): static
    {
        return $this->open(false);
    }

    /**
     * 设置底部按钮（快捷方法：确定+取消）
     * @param string $okText 确定按钮文字
     * @param string $okAction 确定按钮触发的动作
     * @param string $okVariant 确定按钮样式
     * @param string $cancelText 取消按钮文字
     * @param string $cancelVariant 取消按钮样式
     * @return static
     * @ux-example Modal::make()->ok('确认', 'confirmAction', 'primary', '取消', 'secondary')
     */
    public function ok(
        string $okText = '确定',
        string $okAction = '',
        string $okVariant = 'primary',
        string $cancelText = '取消',
        string $cancelVariant = 'secondary'
    ): static {
        $buttons = [
            Button::make()
                ->label($cancelText)
                ->variant($cancelVariant)
                ->attr('data-ux-modal-close', $this->id),
        ];

        if ($okAction) {
            $buttons[] = Button::make()
                ->label($okText)
                ->variant($okVariant)
                ->attr('data-ux-modal-close', $this->id)
                ->attr('data-action', $okAction);
        } else {
            $buttons[] = Button::make()
                ->label($okText)
                ->variant($okVariant)
                ->attr('data-ux-modal-close', $this->id);
        }

        return $this->footer($buttons);
    }

    /**
     * 仅设置取消按钮（快捷方法）
     * @param string $cancelText 取消按钮文字
     * @param string $cancelVariant 取消按钮样式
     * @return static
     * @ux-example Modal::make()->cancel('关闭', 'secondary')
     */
    public function cancel(string $cancelText = '取消', string $cancelVariant = 'secondary'): static
    {
        return $this->footer(
            Button::make()
                ->label($cancelText)
                ->variant($cancelVariant)
                ->attr('data-ux-modal-close', $this->id)
        );
    }

    /**
     * 生成触发按钮
     * @param string $label 按钮文字
     * @param string $variant 按钮变体
     * @return string 渲染后的 HTML
     * @ux-example echo $modal->trigger('打开弹窗')
     */
    public function trigger(string $label, string $variant = 'primary'): string
    {
        return Button::make()
            ->label($label)
            ->variant($variant)
            ->attr('data-ux-modal-open', $this->id)
            ->render();
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-modal');
        if ($this->open) {
            $el->class('ux-modal-open');
        }

        if ($this->backdrop) {
            $el->child(
                Element::make('div')
                    ->class('ux-modal-backdrop')
                    ->data('ux-modal-close', $this->id)
            );
        }

        $dialogEl = Element::make('div');
        $dialogEl->class('ux-modal-dialog');
        $dialogEl->class("ux-modal-{$this->size}");
        if ($this->centered) {
            $dialogEl->class('ux-modal-centered');
        }

        $contentEl = Element::make('div')->class('ux-modal-content');

        if ($this->title || $this->closeable) {
            $headerEl = Element::make('div')->class('ux-modal-header');
            if ($this->title) {
                $headerEl->child(Element::make('h3')->class('ux-modal-title')->text($this->title));
            }
            if ($this->closeable) {
                $headerEl->child(
                    Element::make('button')
                        ->attr('type', 'button')
                        ->class('ux-modal-close')
                        ->data('ux-modal-close', $this->id)
                        ->html('&times;')
                );
            }
            $contentEl->child($headerEl);
        }

        $contentEl->child(
            Element::make('div')
                ->class('ux-modal-body')
                ->html($this->content)
        );

        if ($this->footer) {
            $contentEl->child(
                Element::make('div')
                    ->class('ux-modal-footer')
                    ->child($this->resolveChild($this->footer))
            );
        }

        $dialogEl->child($contentEl);
        $el->child($dialogEl);

        return $el;
    }
}
