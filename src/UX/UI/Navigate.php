<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 导航链接
 *
 * 用于无刷新页面导航，支持目标 URL、片段、加载状态。
 *
 * @ux-category UI
 * @ux-since 1.0.0
 * @ux-example Navigate::make()->url('/page')->label('前往页面')
 * @ux-example Navigate::make()->url('/section')->fragment('content')->child(Button::make()->label('跳转'))
 * @ux-js-component navigate.js
 * @ux-css navigate.css
 */
class Navigate extends UXComponent
{
    protected string $href = '#';
    protected string $text = '';
    protected string $variant = 'primary';
    protected string $size = 'md';
    protected ?string $fragment = null;
    protected string $target = '_self';
    protected bool $replace = false;
    protected ?string $icon = null;
    protected ?string $iconPosition = 'left';
    protected bool $disabled = false;
    protected array $states = [];

    /**
     * 设置导航地址
     * @param string $href 目标 URL
     * @return static
     * @ux-example Navigate::make()->href('/about')
     */
    public function href(string $href): static
    {
        $this->href = $href;
        return $this;
    }

    /**
     * 设置显示文本
     * @param string $text 显示文字
     * @return static
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * 设置导航片段（页面内锚点）
     * @param string $name 片段名称
     * @return static
     */
    public function fragment(string $name): static
    {
        $this->fragment = $name;
        return $this;
    }

    /**
     * 设置打开目标
     * @param string $target 目标：_self/_blank 等
     * @return static
     * @ux-default '_self'
     */
    public function target(string $target): static
    {
        $this->target = $target;
        return $this;
    }

    /**
     * 在新标签页打开
     * @return static
     */
    public function blank(): static
    {
        return $this->target('_blank');
    }

    /**
     * 启用替换模式（不创建新历史记录）
     * @param bool $replace 是否替换
     * @return static
     * @ux-default false
     */
    public function replace(bool $replace = true): static
    {
        $this->replace = $replace;
        return $this;
    }

    /**
     * 设置图标
     * @param string $icon 图标类名（可省略 bi- 前缀）
     * @param string $position 位置：left/right
     * @return static
     */
    public function icon(string $icon, string $position = 'left'): static
    {
        $icon = str_starts_with($icon, 'bi-') ? $icon : 'bi-' . $icon;
        $this->icon = $icon;
        $this->iconPosition = $position;
        return $this;
    }

    /**
     * 使用 Bootstrap Icons
     * @param string $name 图标名称
     * @param string $position 位置
     * @return static
     */
    public function bi(string $name, string $position = 'left'): static
    {
        return $this->icon($name, $position);
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
     * 设置导航状态数据
     * @param string $key 键
     * @param mixed $value 值
     * @return static
     */
    public function state(string $key, mixed $value): static
    {
        $this->states[$key] = $value;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('a');
        $this->buildElement($el);

        $el->class('ux-navigate');
        $el->class("ux-navigate-{$this->variant}");
        $el->class("ux-navigate-{$this->size}");

        if ($this->target !== '_self') {
            $el->attr('target', $this->target);
        }

        if ($this->replace) {
            $el->data('navigate-replace', '');
        }

        if (!empty($this->states)) {
            $el->data('navigate-state', json_encode($this->states, JSON_UNESCAPED_UNICODE));
        }

        if ($this->disabled) {
            $el->class('ux-navigate-disabled');
            $el->attr('aria-disabled', 'true');
            $el->attr('tabindex', '-1');
        } else {
            $el->attr('href', $this->href);
            $el->data('navigate', '');

            if ($this->fragment !== null) {
                $el->data('navigate-fragment', $this->fragment);
            }
        }

        if ($this->icon && $this->iconPosition === 'left') {
            $iconEl = Element::make('i')
                ->class($this->icon)
                ->attr('aria-hidden', 'true');
            $el->child($iconEl);
        }

        if ($this->text) {
            $el->child(Element::make('span')->class('ux-navigate-text')->text($this->text));
        }

        if ($this->icon && $this->iconPosition === 'right') {
            $iconEl = Element::make('i')
                ->class($this->icon)
                ->attr('aria-hidden', 'true');
            $el->child($iconEl);
        }

        return $el;
    }
}
