<?php

declare(strict_types=1);

namespace Framework\UX\Feedback;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 加载遮罩层组件
 *
 * 用于在数据加载、表单提交等操作时显示加载状态，阻止用户交互。
 * 支持全屏和局部两种模式，提供 spinner/dots/bar 三种加载指示器。
 *
 * @ux-category Feedback
 * @ux-since 1.0.0
 * @ux-example LoadingOverlay::make()->id('page-loader')->fullscreen()
 * @ux-example LoadingOverlay::make()->id('table-loader')->inline()->text('加载中...')
 * @ux-example LoadingOverlay::make()->id('form-loader')->type('dots')->text('处理中')
 * @ux-live-support open, close
 * @ux-js-component loading-overlay.js
 * @ux-css loading-overlay.css
 * @ux-value-type string
 */
class LoadingOverlay extends UXComponent
{
    protected string $text = '加载中...';
    protected string $type = 'spinner';
    protected bool $fullscreen = false;
    protected bool $transparent = false;
    protected string $size = 'md';
    protected bool $open = false;

    /**
     * 设置加载提示文字
     * @param string $text 提示文字
     * @return static
     * @ux-example LoadingOverlay::make()->text('正在加载数据...')
     * @ux-default '加载中...'
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * 设置加载指示器类型
     * @param string $type 类型：spinner/dots/bar
     * @return static
     * @ux-example LoadingOverlay::make()->type('dots')
     * @ux-default 'spinner'
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置为全屏遮罩
     * @param bool $fullscreen 是否全屏
     * @return static
     * @ux-example LoadingOverlay::make()->fullscreen()
     * @ux-default false
     */
    public function fullscreen(bool $fullscreen = true): static
    {
        $this->fullscreen = $fullscreen;
        return $this;
    }

    /**
     * 设置为透明背景
     * @param bool $transparent 是否透明
     * @return static
     * @ux-example LoadingOverlay::make()->transparent()
     * @ux-default false
     */
    public function transparent(bool $transparent = true): static
    {
        $this->transparent = $transparent;
        return $this;
    }

    /**
     * 设置加载指示器大小
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-example LoadingOverlay::make()->size('lg')
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 小尺寸指示器
     * @return static
     * @ux-example LoadingOverlay::make()->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸指示器
     * @return static
     * @ux-example LoadingOverlay::make()->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置为内联模式（非全屏）
     * @return static
     * @ux-example LoadingOverlay::make()->inline()
     */
    public function inline(): static
    {
        $this->fullscreen = false;
        return $this;
    }

    /**
     * 设置显示状态
     * @param bool $open 是否显示
     * @return static
     * @ux-example LoadingOverlay::make()->id('loader')->open(true)
     * @ux-default false
     */
    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    /**
     * 关闭加载遮罩
     * @return static
     * @ux-example LoadingOverlay::make()->id('loader')->close()
     */
    public function close(): static
    {
        return $this->open(false);
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);

        $wrapper->class('ux-loading-overlay');
        $wrapper->data('ux-loading-id', $this->id);

        if ($this->fullscreen) {
            $wrapper->class('ux-loading-fullscreen');
        } else {
            $wrapper->class('ux-loading-inline');
        }

        if ($this->transparent) {
            $wrapper->class('ux-loading-transparent');
        }

        if ($this->open) {
            $wrapper->class('ux-loading-active');
        }

        $content = Element::make('div')->class('ux-loading-content');
        $content->child($this->buildIndicator());

        if ($this->text) {
            $content->child(
                Element::make('p')->class('ux-loading-text')->text($this->text)
            );
        }

        $wrapper->child($content);

        return $wrapper;
    }

    /**
     * @ux-internal
     */
    protected function buildIndicator(): Element
    {
        return match ($this->type) {
            'dots' => $this->buildDotsIndicator(),
            'bar' => $this->buildBarIndicator(),
            default => $this->buildSpinnerIndicator(),
        };
    }

    /**
     * @ux-internal
     */
    protected function buildSpinnerIndicator(): Element
    {
        $spinner = Element::make('div')
            ->class('ux-loading-spinner')
            ->class("ux-loading-spinner-{$this->size}");

        $spinner->child(
            Element::make('div')->class('ux-loading-spinner-circle')
        );

        return $spinner;
    }

    /**
     * @ux-internal
     */
    protected function buildDotsIndicator(): Element
    {
        $dots = Element::make('div')
            ->class('ux-loading-dots')
            ->class("ux-loading-dots-{$this->size}");

        for ($i = 0; $i < 3; $i++) {
            $dots->child(
                Element::make('span')->class('ux-loading-dot')
            );
        }

        return $dots;
    }

    /**
     * @ux-internal
     */
    protected function buildBarIndicator(): Element
    {
        $bar = Element::make('div')
            ->class('ux-loading-bar')
            ->class("ux-loading-bar-{$this->size}");

        $bar->child(
            Element::make('div')->class('ux-loading-bar-progress')
        );

        return $bar;
    }
}
