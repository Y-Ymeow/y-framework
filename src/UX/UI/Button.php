<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

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

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function submit(): static
    {
        $this->type = 'submit';
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function primary(): static
    {
        return $this->variant('primary');
    }

    public function secondary(): static
    {
        return $this->variant('secondary');
    }

    public function danger(): static
    {
        return $this->variant('danger');
    }

    public function success(): static
    {
        return $this->variant('success');
    }

    public function warning(): static
    {
        return $this->variant('warning');
    }

    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function sm(): static
    {
        return $this->size('sm');
    }

    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置图标
     * @param string $icon 图标名称（不含前缀），如 'pencil', 'trash'
     * @param string $position 位置：'left' 或 'right'
     * @param string $family 图标库前缀，默认 'bi'（Bootstrap Icons）
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
     */
    public function bi(string $name, string $position = 'left'): static
    {
        $name = str_starts_with($name, 'bi-') ? $name : 'bi-' . $name;
        return $this->icon($name, $position, 'bi');
    }

    public function loading(bool $loading = true): static
    {
        $this->loading = $loading;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function outline(bool $outline = true): static
    {
        $this->outline = $outline;
        return $this;
    }

    public function block(bool $block = true): static
    {
        $this->block = $block;
        return $this;
    }

    /**
     * 将按钮设置为链接模式
     * @param string $href 链接地址
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
     */
    public function navigate(string $url, ?string $fragment = null): static
    {
        $this->href = $url;
        $this->navigate = true;
        $this->navigateFragment = $fragment;
        return $this;
    }

    public function openModal(string $modalId): static
    {
        return $this->dispatch('modal:open', "{ id: '{$modalId}' }");
    }

    public function closeModal(?string $modalId = null): static
    {
        $detail = $modalId ? "{ id: '{$modalId}' }" : null;
        return $this->dispatch('modal:close', $detail);
    }

    public function showToast(string $message, string $type = 'success'): static
    {
        return $this->dispatch('toast:show', "{ message: '{$message}', type: '{$type}' }");
    }

    /**
     * 获取是否为链接模式
     */
    protected function isLinkMode(): bool
    {
        return $this->href !== null;
    }

    /**
     * 获取根标签名
     */
    protected function rootTag(): string
    {
        return $this->isLinkMode() ? 'a' : 'button';
    }

    /**
     * 生成图标 HTML
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
