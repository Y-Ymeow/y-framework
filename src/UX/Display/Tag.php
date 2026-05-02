<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 标签
 *
 * 用于标记和分类，支持多种颜色变体、尺寸、可关闭、带边框。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Tag::make()->text('标签')->primary()
 * @ux-example Tag::make()->text('成功')->success()->closable()
 * @ux-example Tag::make()->text('警告')->warning()->bordered()
 * @ux-live-support liveAction
 * @ux-js-component —
 * @ux-css tag.css
 * @ux-value-type string
 */
class Tag extends UXComponent
{
    protected string $text = '';
    protected string $variant = 'default';
    protected string $size = 'md';
    protected ?string $icon = null;
    protected bool $closable = false;
    protected bool $bordered = false;
    protected ?string $closeAction = null;
    protected ?string $closeEvent = null;

    /**
     * 设置标签文本
     * @param string $text 文本内容
     * @return static
     * @ux-example Tag::make()->text('新标签')
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * 设置颜色变体
     * @param string $variant 变体名：default/primary/success/warning/danger/info
     * @return static
     * @ux-example Tag::make()->text('标签')->variant('primary')
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 默认变体
     * @return static
     * @ux-example Tag::make()->text('默认')->default()
     */
    public function default(): static
    {
        return $this->variant('default');
    }

    /**
     * 主色变体
     * @return static
     * @ux-example Tag::make()->text('主色')->primary()
     */
    public function primary(): static
    {
        return $this->variant('primary');
    }

    /**
     * 成功变体
     * @return static
     * @ux-example Tag::make()->text('成功')->success()
     */
    public function success(): static
    {
        return $this->variant('success');
    }

    /**
     * 警告变体
     * @return static
     * @ux-example Tag::make()->text('警告')->warning()
     */
    public function warning(): static
    {
        return $this->variant('warning');
    }

    /**
     * 危险变体
     * @return static
     * @ux-example Tag::make()->text('危险')->danger()
     */
    public function danger(): static
    {
        return $this->variant('danger');
    }

    /**
     * 信息变体
     * @return static
     * @ux-example Tag::make()->text('信息')->info()
     */
    public function info(): static
    {
        return $this->variant('info');
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-example Tag::make()->text('小标签')->size('sm')
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
     * @ux-example Tag::make()->text('小')->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example Tag::make()->text('大')->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置图标
     * @param string $icon Bootstrap Icons 类名（可省略 bi- 前缀）
     * @return static
     * @ux-example Tag::make()->text('星标')->icon('star-fill')
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * 设置可关闭
     * @param bool $closable 是否可关闭
     * @return static
     * @ux-example Tag::make()->text('可关闭')->closable()
     * @ux-default true
     */
    public function closable(bool $closable = true): static
    {
        $this->closable = $closable;
        return $this;
    }

    /**
     * 设置带边框
     * @param bool $bordered 是否带边框
     * @return static
     * @ux-example Tag::make()->text('带边框')->bordered()
     * @ux-default true
     */
    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    /**
     * 设置关闭按钮的 LiveAction
     * @param string $action Action 名称
     * @param string $event 触发事件
     * @return static
     * @ux-example Tag::make()->text('删除')->closable()->onClose('removeTag')
     * @ux-default event='click'
     */
    public function onClose(string $action, string $event = 'click'): static
    {
        $this->closeAction = $action;
        $this->closeEvent = $event;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('span');
        $this->buildElement($el);

        $el->class('ux-tag');
        $el->class("ux-tag-{$this->variant}");
        $el->class("ux-tag-{$this->size}");

        if ($this->bordered) {
            $el->class('ux-tag-bordered');
        }

        if ($this->closable) {
            $el->class('ux-tag-closable');
        }

        if ($this->icon) {
            $iconClass = str_starts_with($this->icon, 'bi-') ? $this->icon : 'bi-' . $this->icon;
            $el->child(
                Element::make('i')
                    ->class($iconClass)
                    ->class('ux-tag-icon')
            );
        }

        $el->child(
            Element::make('span')
                ->class('ux-tag-text')
                ->text($this->text)
        );

        if ($this->closable) {
            $closeEl = Element::make('span')
                ->class('ux-tag-close')
                ->html('<i class="bi bi-x"></i>');

            if ($this->closeAction) {
                $closeEl->liveAction($this->closeAction, $this->closeEvent ?? 'click');
            } else {
                $closeEl->data('tag-close', '');
            }

            $el->child($closeEl);
        }

        return $el;
    }
}
