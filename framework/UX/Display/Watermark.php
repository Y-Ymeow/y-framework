<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 水印
 *
 * 用于在内容上添加水印，支持自定义文字、字体大小、颜色、旋转角度、间距、层级。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Watermark::make()->content('机密')->fontSize(20)->rotate(-45)
 * @ux-example Watermark::make()->content('预览')->gap(150, 150)->zIndex(1)
 * @ux-js-component —
 * @ux-css watermark.css
 */
class Watermark extends UXComponent
{
    protected string $content = '';
    protected int $fontSize = 16;
    protected ?string $fontColor = 'rgba(0, 0, 0, 0.15)';
    protected int $rotate = -30;
    protected int $gapX = 100;
    protected int $gapY = 100;
    protected int $zIndex = 9;

    /**
     * 设置水印文字内容
     * @param string $content 水印文字
     * @return static
     * @ux-example Watermark::make()->content('机密')
     */
    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置水印字体大小
     * @param int $size 字体大小（px）
     * @return static
     * @ux-example Watermark::make()->fontSize(20)
     * @ux-default 16
     */
    public function fontSize(int $size): static
    {
        $this->fontSize = $size;
        return $this;
    }

    /**
     * 设置水印字体颜色
     * @param string $color 颜色（支持 rgba）
     * @return static
     * @ux-example Watermark::make()->fontColor('rgba(0,0,0,0.15)')
     */
    public function fontColor(string $color): static
    {
        $this->fontColor = $color;
        return $this;
    }

    /**
     * 设置水印旋转角度
     * @param int $rotate 旋转角度（度，负值为顺时针）
     * @return static
     * @ux-example Watermark::make()->rotate(-45)
     * @ux-default -30
     */
    public function rotate(int $rotate): static
    {
        $this->rotate = $rotate;
        return $this;
    }

    /**
     * 设置水印间距
     * @param int $x 水平间距（px）
     * @param int $y 垂直间距（px）
     * @return static
     * @ux-example Watermark::make()->gap(150, 150)
     * @ux-default x=100, y=100
     */
    public function gap(int $x, int $y): static
    {
        $this->gapX = $x;
        $this->gapY = $y;
        return $this;
    }

    /**
     * 设置水印层级
     * @param int $zIndex z-index 值
     * @return static
     * @ux-example Watermark::make()->zIndex(9)
     * @ux-default 9
     */
    public function zIndex(int $zIndex): static
    {
        $this->zIndex = $zIndex;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-watermark-wrapper');

        // 内容区域
        $contentEl = Element::make('div')->class('ux-watermark-content');
        $this->appendChildren($contentEl);
        $el->child($contentEl);

        // 水印层
        $markEl = Element::make('div')->class('ux-watermark');
        $markEl->data('watermark-content', $this->content);
        $markEl->data('watermark-font-size', (string)$this->fontSize);
        $markEl->data('watermark-color', $this->fontColor);
        $markEl->data('watermark-rotate', (string)$this->rotate);
        $markEl->data('watermark-gap-x', (string)$this->gapX);
        $markEl->data('watermark-gap-y', (string)$this->gapY);
        $markEl->style("z-index: {$this->zIndex}");
        $el->child($markEl);

        return $el;
    }
}
