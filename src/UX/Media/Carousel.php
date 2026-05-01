<?php

declare(strict_types=1);

namespace Framework\UX\Media;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Carousel extends UXComponent
{
    protected array $items = [];
    protected bool $autoplay = false;
    protected int $interval = 3000;
    protected bool $dots = true;
    protected bool $arrows = true;
    protected string $effect = 'scrollx';
    protected bool $loop = true;
    protected ?string $action = null;

    public function item(string $content, ?string $title = null): static
    {
        $this->items[] = ['content' => $content, 'title' => $title];
        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function autoplay(bool $autoplay = true, int $interval = 3000): static
    {
        $this->autoplay = $autoplay;
        $this->interval = $interval;
        return $this;
    }

    public function dots(bool $dots = true): static
    {
        $this->dots = $dots;
        return $this;
    }

    public function arrows(bool $arrows = true): static
    {
        $this->arrows = $arrows;
        return $this;
    }

    public function effect(string $effect): static
    {
        $this->effect = $effect;
        return $this;
    }

    public function fade(): static
    {
        return $this->effect('fade');
    }

    public function loop(bool $loop = true): static
    {
        $this->loop = $loop;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-carousel');
        $el->class("ux-carousel-{$this->effect}");

        $el->data('carousel-autoplay', $this->autoplay ? 'true' : 'false');
        $el->data('carousel-interval', (string)$this->interval);
        $el->data('carousel-loop', $this->loop ? 'true' : 'false');

        if ($this->action) {
            $el->data('carousel-action', $this->action);
        }

        // 轨道
        $trackEl = Element::make('div')->class('ux-carousel-track');

        foreach ($this->items as $index => $item) {
            $slideEl = Element::make('div')
                ->class('ux-carousel-slide')
                ->data('index', (string)$index);

            if (is_string($item)) {
                $slideEl->html($item);
            } elseif (is_array($item)) {
                $slideEl->html($item['content'] ?? '');
            }

            $trackEl->child($slideEl);
        }

        $el->child($trackEl);

        // 箭头
        if ($this->arrows) {
            $prevEl = Element::make('button')
                ->class('ux-carousel-arrow')
                ->class('ux-carousel-arrow-prev')
                ->html('<i class="bi bi-chevron-left"></i>');
            $el->child($prevEl);

            $nextEl = Element::make('button')
                ->class('ux-carousel-arrow')
                ->class('ux-carousel-arrow-next')
                ->html('<i class="bi bi-chevron-right"></i>');
            $el->child($nextEl);
        }

        // 指示点
        if ($this->dots) {
            $dotsEl = Element::make('div')->class('ux-carousel-dots');
            foreach ($this->items as $index => $item) {
                $dotEl = Element::make('button')
                    ->class('ux-carousel-dot')
                    ->class($index === 0 ? 'active' : '')
                    ->data('index', (string)$index);
                $dotsEl->child($dotEl);
            }
            $el->child($dotsEl);
        }

        return $el;
    }
}
