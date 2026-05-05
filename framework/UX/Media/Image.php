<?php

declare(strict_types=1);

namespace Framework\UX\Media;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 图片
 *
 * 用于显示图片，支持自定义尺寸、占位符、懒加载、预览、失败替换、适配模式。
 *
 * @ux-category Media
 * @ux-since 1.0.0
 * @ux-example Image::make()->src('/photo.jpg')->width(300)->height(200)
 * @ux-example Image::make()->src('/photo.jpg')->preview()->lazy()
 * @ux-example Image::make()->src('/photo.jpg')->fit('cover')->fallback('/fallback.jpg')
 * @ux-js-component image.js
 * @ux-css image.css
 */
class Image extends UXComponent
{
    protected string $src = '';
    protected ?string $alt = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected bool $preview = false;
    protected bool $lazy = false;
    protected ?string $fallback = null;
    protected string $fit = 'fill';

    /**
     * 设置图片源地址
     * @param string $src 图片 URL
     * @return static
     * @ux-example Image::make()->src('/photo.jpg')
     */
    public function src(string $src): static
    {
        $this->src = $src;
        return $this;
    }

    /**
     * 设置图片替代文本
     * @param string $alt 替代文本
     * @return static
     * @ux-example Image::make()->alt('描述文字')
     */
    public function alt(string $alt): static
    {
        $this->alt = $alt;
        return $this;
    }

    /**
     * 设置图片宽度
     * @param int $width 宽度（px）
     * @return static
     * @ux-example Image::make()->width(300)
     */
    public function width(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * 设置图片高度
     * @param int $height 高度（px）
     * @return static
     * @ux-example Image::make()->height(200)
     */
    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 启用预览（点击放大）
     * @param bool $preview 是否启用
     * @return static
     * @ux-example Image::make()->preview()
     * @ux-default true
     */
    public function preview(bool $preview = true): static
    {
        $this->preview = $preview;
        return $this;
    }

    /**
     * 启用懒加载
     * @param bool $lazy 是否启用
     * @return static
     * @ux-example Image::make()->lazy()
     * @ux-default true
     */
    public function lazy(bool $lazy = true): static
    {
        $this->lazy = $lazy;
        return $this;
    }

    /**
     * 设置失败替换图片
     * @param string $fallback 替换图片 URL
     * @return static
     * @ux-example Image::make()->fallback('/default.jpg')
     */
    public function fallback(string $fallback): static
    {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * 设置适配模式
     * @param string $fit 模式：fill/contain/cover/scale-down
     * @return static
     * @ux-example Image::make()->fit('cover')
     * @ux-default 'fill'
     */
    public function fit(string $fit): static
    {
        $this->fit = $fit;
        return $this;
    }

    /**
     * 等比适配（保留宽高比，可能有留白）
     * @return static
     * @ux-example Image::make()->contain()
     */
    public function contain(): static
    {
        return $this->fit('contain');
    }

    /**
     * 覆盖适配（填满容器，可能裁剪）
     * @return static
     * @ux-example Image::make()->cover()
     */
    public function cover(): static
    {
        return $this->fit('cover');
    }

    /**
     * 缩放适配（不超过原始尺寸）
     * @return static
     * @ux-example Image::make()->scaleDown()
     */
    public function scaleDown(): static
    {
        return $this->fit('scale-down');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-image');
        $el->class("ux-image-fit-{$this->fit}");
        if ($this->preview) {
            $el->class('ux-image-preview');
        }

        // 图片元素
        $imgEl = Element::make('img')
            ->attr('src', $this->src)
            ->class('ux-image-img');

        if ($this->alt) {
            $imgEl->attr('alt', $this->alt);
        }
        if ($this->width) {
            $imgEl->attr('width', (string)$this->width);
        }
        if ($this->height) {
            $imgEl->attr('height', (string)$this->height);
        }
        if ($this->lazy) {
            $imgEl->attr('loading', 'lazy');
        }
        if ($this->fallback) {
            $imgEl->attr('onerror', "this.src='{$this->fallback}'");
        }

        $el->child($imgEl);

        // 预览遮罩
        if ($this->preview) {
            $maskEl = Element::make('div')
                ->class('ux-image-mask')
                ->html('<i class="bi bi-zoom-in"></i>');
            $el->child($maskEl);
        }

        return $el;
    }
}
