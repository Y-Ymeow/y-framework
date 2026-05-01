<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class EmptyState extends UXComponent
{
    protected ?string $description = null;
    protected ?string $image = null;
    protected ?string $imageStyle = null;
    protected mixed $extra = null;
    protected string $size = 'md';

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function image(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function imageStyle(string $style): static
    {
        $this->imageStyle = $style;
        return $this;
    }

    public function extra(mixed $extra): static
    {
        $this->extra = $extra;
        return $this;
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

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-empty');
        $el->class("ux-empty-{$this->size}");

        // 图片/图标区域
        $imageEl = Element::make('div')->class('ux-empty-image');
        if ($this->imageStyle) {
            $imageEl->style($this->imageStyle);
        }

        if ($this->image) {
            if (str_starts_with($this->image, '<')) {
                $imageEl->html($this->image);
            } else {
                $imageEl->child(Element::make('img')->attr('src', $this->image)->attr('alt', 'empty'));
            }
        } else {
            // 默认空状态图标
            $imageEl->html($this->getDefaultIcon());
        }
        $el->child($imageEl);

        // 描述文字
        if ($this->description) {
            $el->child(
                Element::make('div')
                    ->class('ux-empty-description')
                    ->text($this->description)
            );
        }

        // 额外内容
        if ($this->extra) {
            $extraEl = Element::make('div')->class('ux-empty-extra');
            if (is_string($this->extra)) {
                $extraEl->html($this->extra);
            } elseif ($this->extra instanceof UXComponent) {
                $extraEl->child($this->extra->toElement());
            } elseif ($this->extra instanceof Element) {
                $extraEl->child($this->extra);
            }
            $el->child($extraEl);
        }

        return $el;
    }

    protected function getDefaultIcon(): string
    {
        // 默认的空状态SVG图标
        return '<svg viewBox="0 0 184 152" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
            <g fill="none" fill-rule="evenodd">
                <g transform="translate(24 31.67)">
                    <ellipse cx="67.797" cy="106.89" rx="67.797" ry="12.668" fill="#f5f5f5" fill-opacity=".8"/>
                    <path d="M122.034 69.674L98.109 40.229c-1.148-1.386-2.826-2.225-4.593-2.225h-51.44c-1.766 0-3.444.839-4.592 2.225L13.56 69.674v15.383h108.475V69.674z" fill="#aeb8c2"/>
                    <path d="M101.537 86.214L80.63 61.102c-1.001-1.207-2.507-1.867-4.048-1.867H31.724c-1.54 0-3.047.66-4.048 1.867L6.769 86.214v13.792h94.768V86.214z" fill="url(#linearGradient-1)" transform="translate(13.56)"/>
                    <path d="M33.83 0h67.933a4 4 0 0 1 4 4v93.344a4 4 0 0 1-4 4H33.83a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z" fill="#f5f5f5"/>
                    <path d="M42.678 9.953h50.237a2 2 0 0 1 2 2V36.91a2 2 0 0 1-2 2H42.678a2 2 0 0 1-2-2V11.953a2 2 0 0 1 2-2zM42.94 49.767h49.713a2.262 2.262 0 1 1 0 4.524H42.94a2.262 2.262 0 0 1 0-4.524zM42.94 61.53h49.713a2.262 2.262 0 1 1 0 4.525H42.94a2.262 2.262 0 0 1 0-4.525zM121.813 105.032c-.775 3.071-3.497 5.36-6.735 5.36H20.515c-3.238 0-5.96-2.29-6.734-5.36a7.309 7.309 0 0 1-.222-1.79V69.675h26.318c2.907 0 5.25 2.448 5.25 5.42v.04c0 2.971 2.37 5.37 5.277 5.37h34.785c2.907 0 5.277-2.421 5.277-5.393V75.1c0-2.972 2.343-5.426 5.25-5.426h26.318v33.569c0 .617-.077 1.216-.221 1.789z" fill="#dce0e6"/>
                </g>
                <path d="M149.121 33.292l-6.83 2.65a1 1 0 0 1-1.317-1.23l1.937-6.207c-2.589-2.944-4.109-6.534-4.109-10.408C138.802 8.102 148.92 0 161.402 0 173.881 0 184 8.102 184 18.097c0 9.995-10.118 18.097-22.599 18.097-4.528 0-8.744-1.066-12.28-2.902z" fill="#dce0e6"/>
                <g transform="translate(149.65 15.383)" fill="#fff">
                    <ellipse cx="20.654" cy="3.167" rx="2.849" ry="2.815"/>
                    <path d="M5.698 5.63H0L2.898.704zM9.259.704h4.985V5.63H9.259z"/>
                </g>
            </g>
            <defs>
                <linearGradient id="linearGradient-1" x1="50%" y1="0%" x2="50%" y2="100%">
                    <stop offset="0%" stop-color="#f5f5f5" stop-opacity="0"/>
                    <stop offset="100%" stop-color="#f5f5f5" stop-opacity="1"/>
                </linearGradient>
            </defs>
        </svg>';
    }
}
