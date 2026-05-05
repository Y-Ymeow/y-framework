<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 按钮
 *
 * 用于触发操作，支持多种颜色变体、尺寸、图标、加载、禁用、块级、链接模式。
 *
 * @ux-category UI
 * @ux-since 1.0.0
 * @ux-example Button::make()->label('提交')->primary()->liveAction('save')
 * @ux-example Button::make()->label('危险')->danger()->outline()
 * @ux-example Button::make()->label('带图标')->icon('pencil', 'left')->bi('edit')
 * @ux-example Button::make()->label('链接')->navigate('/page')
 * @ux-js-component —
 * @ux-css button.css
 */
class Button extends UXComponent
{
    protected string $label = '';
    protected string $type = 'button';
    protected string $variant = 'primary';
    protected string $size = 'md';
    protected ?string $icon = null;
    protected ?string $iconPosition = 'left';
    protected string $iconFamily = 'bi';
    protected bool $loading = false;
    protected bool $disabled = false;
    protected bool $outline = false;
    protected bool $block = false;
    protected ?string $href = null;
    protected bool $navigate = false;
    protected ?string $navigateFragment = null;

    /**
     * 设置按钮文本
     * @param string $label 按钮文字
     * @return static
     * @ux-example Button::make()->label('提交')
     */
    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * 设置按钮类型
     * @param string $type 类型：button/submit/reset
     * @return static
     * @ux-default 'button'
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置为提交按钮
     * @return static
     */
    public function submit(): static
    {
        $this->type = 'submit';
        return $this;
    }

    /**
     * 设置颜色变体
     * @param string $variant 变体名
     * @return static
     * @ux-default 'primary'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 主色变体
     * @return static
     */
    public function primary(): static
    {
        return $this->variant('primary');
    }

    /**
     * 次色变体
     * @return static
     */
    public function secondary(): static
    {
        return $this->variant('secondary');
    }

    /**
     * 危险变体
     * @return static
     */
    public function danger(): static
    {
        return $this->variant('danger');
    }

    /**
     * 成功变体
     * @return static
     */
    public function success(): static
    {
        return $this->variant('success');
    }

    /**
     * 警告变体
     * @return static
     */
    public function warning(): static
    {
        return $this->variant('warning');
    }

    /**
     * 设置尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
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
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置图标
     * @param string $icon 图标名称（不含前缀），如 'pencil', 'trash'
     * @param string $position 位置：'left' 或 'right'
     * @param string $family 图标库前缀，默认 'bi'（Bootstrap Icons）
     * @return static
     * @ux-example Button::make()->label('编辑')->icon('pencil', 'left')
     */
    public function icon(string $icon, string $position = 'left', string $family = 'bi'): static
    {
        $this->icon = $icon;
        $this->iconPosition = $position;
        $this->iconFamily = $family;
        return $this;
    }

    /**
     * 使用 Bootstrap Icons
     * @param string $name 图标名称，如 'bi-pencil', 'pencil'
     * @param string $position 位置
     * @return static
     */
    public function bi(string $name, string $position = 'left'): static
    {
        $name = str_starts_with($name, 'bi-') ? $name : 'bi-' . $name;
        return $this->icon($name, $position, 'bi');
    }

    /**
     * 设置加载状态
     * @param bool $loading 是否加载
     * @return static
     * @ux-default false
     */
    public function loading(bool $loading = true): static
    {
        $this->loading = $loading;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-default false
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置边框模式
     * @param bool $outline 是否边框
     * @return static
     * @ux-default false
     */
    public function outline(bool $outline = true): static
    {
        $this->outline = $outline;
        return $this;
    }

    /**
     * 设置块级按钮（全宽）
     * @param bool $block 是否块级
     * @return static
     * @ux-default false
     */
    public function block(bool $block = true): static
    {
        $this->block = $block;
        return $this;
    }

    /**
     * 将按钮设置为链接模式
     * @param string $href 链接地址
     * @return static
     * @ux-example Button::make()->label('跳转')->href('/page')
     */
    public function href(string $href): static
    {
        $this->href = $href;
        return $this;
    }

    /**
     * 启用无刷新导航（data-navigate）
     * @param string $url 导航地址
     * @param string|null $fragment 可选的目标 fragment 名称
     * @return static
     * @ux-example Button::make()->label('跳转')->navigate('/section', 'content')
     */
    public function navigate(string $url, ?string $fragment = null): static
    {
        $this->href = $url;
        $this->navigate = true;
        $this->navigateFragment = $fragment;
        return $this;
    }

    /**
     * 触发打开模态框事件
     * @param string $modalId 模态框 ID
     * @return static
     */
    public function openModal(string $modalId): static
    {
        return $this->dispatch('modal:open', "{ id: '{$modalId}' }");
    }

    /**
     * 触发关闭模态框事件
     * @param string|null $modalId 模态框 ID（可选）
     * @return static
     */
    public function closeModal(?string $modalId = null): static
    {
        $detail = $modalId ? "{ id: '{$modalId}' }" : null;
        return $this->dispatch('modal:close', $detail);
    }

    /**
     * 触发显示 Toast 事件
     * @param string $message 消息内容
     * @param string $type 类型：success/error/warning/info
     * @return static
     */
    public function showToast(string $message, string $type = 'success'): static
    {
        return $this->dispatch('toast:show', "{ message: '{$message}', type: '{$type}' }");
    }

    /**
     * 获取是否为链接模式
     * @ux-internal
     */
    protected function isLinkMode(): bool
    {
        return $this->href !== null;
    }

    /**
     * 获取根标签名
     * @ux-internal
     */
    protected function rootTag(): string
    {
        return $this->isLinkMode() ? 'a' : 'button';
    }

    /**
     * 生成图标 HTML
     * @ux-internal
     */
    protected function renderIcon(): string
    {
        if (!$this->icon) {
            return '';
        }

        if ($this->iconFamily === 'bi') {
            $iconClass = str_starts_with($this->icon, 'bi-') ? $this->icon : 'bi-' . $this->icon;
            return '<i class="' . htmlspecialchars($iconClass) . '" aria-hidden="true"></i>';
        }

        return htmlspecialchars($this->icon);
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $tag = $this->isLinkMode() ? 'a' : 'button';
        $el = new Element($tag);
        $this->buildElement($el);

        $el->class('ux-btn');
        $el->class("ux-btn-{$this->variant}");
        if ($this->outline) {
            $el->class('ux-btn-outline');
        }
        $el->class("ux-btn-{$this->size}");
        if ($this->block) {
            $el->class('ux-btn-block');
        }
        if ($this->loading) {
            $el->class('ux-btn-loading');
        }
        if ($this->disabled) {
            $el->class('ux-btn-disabled');
        }

        if ($this->isLinkMode()) {
            $el->attr('href', $this->href);
            if ($this->navigate) {
                $el->data('navigate', '');
                if ($this->navigateFragment !== null) {
                    $el->data('navigate-fragment', $this->navigateFragment);
                }
            }
        } else {
            $el->attr('type', $this->type);

            if ($this->disabled || $this->loading) {
                $el->attr('disabled', '');
            }
        }

        if ($this->loading) {
            $el->data('loading', 'true');
            $el->child(Element::make('span')->class('ux-btn-spinner'));
        } elseif ($this->icon && $this->iconPosition === 'left') {
            $el->child(Element::make('span')->class('ux-btn-icon')->html($this->renderIcon()));
        }

        if ($this->label) {
            $el->child(Element::make('span')->class('ux-btn-label')->text($this->label));
        }

        if (!$this->loading && $this->icon && $this->iconPosition === 'right') {
            $el->child(Element::make('span')->class('ux-btn-icon')->html($this->renderIcon()));
        }

        return $el;
    }
}
