<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 二维码
 *
 * 用于生成二维码，支持自定义内容、尺寸、纠错级别、图标、颜色。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example QRCode::make()->value('https://example.com')->size(200)
 * @ux-example QRCode::make()->value('文本')->icon('/logo.png')->iconSize(40)
 * @ux-js-component qrcode.js
 * @ux-css qrcode.css
 */
class QRCode extends UXComponent
{
    protected string $value = '';
    protected int $size = 128;
    protected string $level = 'M';
    protected ?string $icon = null;
    protected int $iconSize = 32;
    protected ?string $color = '#000000';
    protected ?string $bgColor = '#ffffff';

    /**
     * 设置二维码内容
     * @param string $value 二维码内容（URL/文本）
     * @return static
     * @ux-example QRCode::make()->value('https://example.com')
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置二维码尺寸
     * @param int $size 尺寸（像素）
     * @return static
     * @ux-example QRCode::make()->size(200)
     * @ux-default 128
     */
    public function size(int $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 设置纠错级别
     * @param string $level 级别：L/M/Q/H
     * @return static
     * @ux-example QRCode::make()->level('H')
     * @ux-default 'M'
     */
    public function level(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    /**
     * 设置中心图标
     * @param string $icon 图标 URL
     * @param int $size 图标尺寸（像素）
     * @return static
     * @ux-example QRCode::make()->icon('/logo.png', 40)
     * @ux-default size=32
     */
    public function icon(string $icon, int $size = 32): static
    {
        $this->icon = $icon;
        $this->iconSize = $size;
        return $this;
    }

    /**
     * 设置前景色（二维码颜色）
     * @param string $color 颜色（十六进制）
     * @return static
     * @ux-example QRCode::make()->color('#000000')
     */
    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * 设置背景色
     * @param string $bgColor 背景颜色（十六进制）
     * @return static
     * @ux-example QRCode::make()->bgColor('#ffffff')
     */
    public function bgColor(string $bgColor): static
    {
        $this->bgColor = $bgColor;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-qrcode');
        $el->data('qrcode-value', $this->value);
        $el->data('qrcode-size', (string)$this->size);
        $el->data('qrcode-level', $this->level);

        if ($this->color) {
            $el->data('qrcode-color', $this->color);
        }
        if ($this->bgColor) {
            $el->data('qrcode-bg', $this->bgColor);
        }
        if ($this->icon) {
            $el->data('qrcode-icon', $this->icon);
            $el->data('qrcode-icon-size', (string)$this->iconSize);
        }

        // Canvas 容器
        $canvasEl = Element::make('canvas')
            ->class('ux-qrcode-canvas')
            ->attr('width', (string)$this->size)
            ->attr('height', (string)$this->size);
        $el->child($canvasEl);

        return $el;
    }
}
