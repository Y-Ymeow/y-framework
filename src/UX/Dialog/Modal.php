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

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function content(mixed $content): static
    {
        $this->content = is_string($content) ? $content : $this->resolveValue($content);
        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function sm(): static { return $this->size('sm'); }
    public function lg(): static { return $this->size('lg'); }
    public function xl(): static { return $this->size('xl'); }
    public function fullscreen(): static { return $this->size('fullscreen'); }

    public function closeable(bool $closeable = true): static
    {
        $this->closeable = $closeable;
        return $this;
    }

    public function backdrop(bool $backdrop = true): static
    {
        $this->backdrop = $backdrop;
        return $this;
    }

    public function centered(bool $centered = true): static
    {
        $this->centered = $centered;
        return $this;
    }

    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
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

    public function trigger(string $label, string $variant = 'primary'): string
    {
        return Button::make()
            ->label($label)
            ->variant($variant)
            ->attr('data-ux-modal-open', $this->id)
            ->render();
    }
}
