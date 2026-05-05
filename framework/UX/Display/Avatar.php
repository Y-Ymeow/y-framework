<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 头像
 *
 * 用于显示用户头像，支持图片、姓名首字母、尺寸、形状、状态指示器。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Avatar::make()->src('/user.jpg')->size('lg')
 * @ux-example Avatar::make()->name('张三')->circle()
 * @ux-example Avatar::make()->name('李四')->status('online')
 * @ux-js-component —
 * @ux-css avatar.css
 */
class Avatar extends UXComponent
{
    protected ?string $src = null;
    protected ?string $name = null;
    protected string $size = 'md';
    protected string $shape = 'circle';
    protected ?string $status = null;

    /**
     * 设置头像图片源
     * @param string $src 图片 URL
     * @return static
     * @ux-example Avatar::make()->src('/user.jpg')
     */
    public function src(string $src): static
    {
        $this->src = $src;
        return $this;
    }

    /**
     * 设置用户姓名（用于显示首字母）
     * @param string $name 用户姓名
     * @return static
     * @ux-example Avatar::make()->name('张三')
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 设置头像尺寸
     * @param string $size 尺寸：sm/md/lg/xl
     * @return static
     * @ux-example Avatar::make()->size('lg')
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 设置头像形状
     * @param string $shape 形状：circle/rounded
     * @return static
     * @ux-example Avatar::make()->shape('rounded')
     * @ux-default 'circle'
     */
    public function shape(string $shape): static
    {
        $this->shape = $shape;
        return $this;
    }

    /**
     * 圆形头像
     * @return static
     * @ux-example Avatar::make()->circle()
     */
    public function circle(): static
    {
        return $this->shape('circle');
    }

    /**
     * 圆角头像
     * @return static
     * @ux-example Avatar::make()->rounded()
     */
    public function rounded(): static
    {
        return $this->shape('rounded');
    }

    /**
     * 设置状态指示器
     * @param string $status 状态：online/offline/busy/away
     * @return static
     * @ux-example Avatar::make()->status('online')
     */
    public function status(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    protected function getInitials(): string
    {
        if (!$this->name) return '';
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $w) {
            $initials .= mb_substr($w, 0, 1);
        }
        return mb_strtoupper(mb_substr($initials, 0, 2));
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-avatar');
        $el->class("ux-avatar-{$this->size}");
        $el->class("ux-avatar-{$this->shape}");

        if ($this->src) {
            $el->child(
                Element::make('img')
                    ->class('ux-avatar-img')
                    ->attr('src', $this->src)
                    ->attr('alt', $this->name ?? 'Avatar')
            );
        } elseif ($this->name) {
            $el->child(
                Element::make('span')
                    ->class('ux-avatar-initials')
                    ->text($this->getInitials())
            );
        } else {
            $el->child(
                Element::make('span')
                    ->class('ux-avatar-placeholder')
                    ->text('?')
            );
        }

        if ($this->status) {
            $el->child(
                Element::make('span')
                    ->class('ux-avatar-status')
                    ->class("ux-avatar-status-{$this->status}")
            );
        }

        return $el;
    }
}
