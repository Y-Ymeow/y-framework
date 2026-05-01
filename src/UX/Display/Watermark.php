<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Watermark extends UXComponent
{
    protected string $content = '';
    protected int $fontSize = 16;
    protected ?string $fontColor = 'rgba(0, 0, 0, 0.15)';
    protected int $rotate = -30;
    protected int $gapX = 100;
    protected int $gapY = 100;
    protected int $zIndex = 9;

    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function fontSize(int $size): static
    {
        $this->fontSize = $size;
        return $this;
    }

    public function fontColor(string $color): static
    {
        $this->fontColor = $color;
        return $this;
    }

    public function rotate(int $rotate): static
    {
        $this->rotate = $rotate;
        return $this;
    }

    public function gap(int $x, int $y): static
    {
        $this->gapX = $x;
        $this->gapY = $y;
        return $this;
    }

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
