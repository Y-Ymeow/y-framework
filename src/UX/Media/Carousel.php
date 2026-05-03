<?php

declare(strict_types=1);

namespace Framework\UX\Media;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 轮播图
 *
 * 用于展示多张幻灯片，支持自动播放、指示点、箭头、切换效果、循环。
 *
 * @ux-category Media
 * @ux-since 1.0.0
 * @ux-example Carousel::make()->item('幻灯片1')->item('幻灯片2')->autoplay()
 * @ux-example Carousel::make()->items($slides)->dots()->arrows()->effect('fade')
 * @ux-js-component carousel.js
 * @ux-css carousel.css
 */
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

    /**
     * 添加单个幻灯片
     * @param string $content 内容（HTML 或组件）
     * @param string|null $title 标题
     * @return static
     * @ux-example Carousel::make()->item('幻灯片内容', '标题')
     */
    public function item(string $content, ?string $title = null): static
    {
        $this->items[] = ['content' => $content, 'title' => $title];
        return $this;
    }

    /**
     * 批量设置幻灯片
     * @param array $items 幻灯片数组
     * @return static
     * @ux-example Carousel::make()->items(['幻灯片1', '幻灯片2', '幻灯片3'])
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * 启用自动播放
     * @param bool $autoplay 是否自动播放
     * @param int $interval 播放间隔（毫秒）
     * @return static
     * @ux-example Carousel::make()->autoplay(true, 5000)
     * @ux-default autoplay=true
     * @ux-default interval=3000
     */
    public function autoplay(bool $autoplay = true, int $interval = 3000): static
    {
        $this->autoplay = $autoplay;
        $this->interval = $interval;
        return $this;
    }

    /**
     * 显示指示点
     * @param bool $dots 是否显示
     * @return static
     * @ux-example Carousel::make()->dots(true)
     * @ux-default true
     */
    public function dots(bool $dots = true): static
    {
        $this->dots = $dots;
        return $this;
    }

    /**
     * 显示箭头导航
     * @param bool $arrows 是否显示
     * @return static
     * @ux-example Carousel::make()->arrows(true)
     * @ux-default true
     */
    public function arrows(bool $arrows = true): static
    {
        $this->arrows = $arrows;
        return $this;
    }

    /**
     * 设置切换效果
     * @param string $effect 效果：fade/scrollx
     * @return static
     * @ux-example Carousel::make()->effect('fade')
     * @ux-default 'scrollx'
     */
    public function effect(string $effect): static
    {
        $this->effect = $effect;
        return $this;
    }

    /**
     * 淡入淡出效果
     * @return static
     * @ux-example Carousel::make()->fade()
     */
    public function fade(): static
    {
        return $this->effect('fade');
    }

    /**
     * 启用循环播放
     * @param bool $loop 是否循环
     * @return static
     * @ux-example Carousel::make()->loop(true)
     * @ux-default true
     */
    public function loop(bool $loop = true): static
    {
        $this->loop = $loop;
        return $this;
    }

    /**
     * 设置 LiveAction（点击幻灯片触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example Carousel::make()->action('onSlideClick')
     */
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
