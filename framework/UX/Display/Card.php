<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 卡片
 *
 * 用于展示内容区块，支持标题、副标题、图片、页眉页脚，多种变体。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example Card::make()->title('标题')->subtitle('副标题')->child('内容')
 * @ux-example Card::make()->title('图片卡')->image('/img.jpg', 'top')->child('内容')
 * @ux-example Card::make()->title('带页脚')->footer(Button::make()->label('操作')->primary())
 * @ux-js-component —
 * @ux-css card.css
 */
class Card extends UXComponent
{
    protected ?string $title = null;
    protected ?string $subtitle = null;
    protected mixed $header = null;
    protected mixed $footer = null;
    protected ?string $image = null;
    protected string $imagePosition = 'top';
    protected string $variant = 'default';

    /**
     * 设置卡片标题
     * @param string $title 标题文字
     * @return static
     * @ux-example Card::make()->title('标题')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置卡片副标题
     * @param string $subtitle 副标题文字
     * @return static
     * @ux-example Card::make()->subtitle('副标题')
     */
    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * 设置自定义页眉内容
     * @param mixed $header 页眉内容（字符串/组件）
     * @return static
     * @ux-example Card::make()->header($customHeader)
     */
    public function header(mixed $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 设置自定义页脚内容
     * @param mixed $footer 页脚内容（字符串/组件）
     * @return static
     * @ux-example Card::make()->footer(Button::make()->label('操作')->primary())
     */
    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * 设置卡片图片
     * @param string $src 图片 URL
     * @param string $position 位置：top/bottom
     * @return static
     * @ux-example Card::make()->image('/img.jpg', 'top')
     * @ux-default position='top'
     */
    public function image(string $src, string $position = 'top'): static
    {
        $this->image = $src;
        $this->imagePosition = $position;
        return $this;
    }

    /**
     * 设置卡片变体
     * @param string $variant 变体名：default/bordered/shadow
     * @return static
     * @ux-example Card::make()->variant('shadow')
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 带边框变体
     * @return static
     * @ux-example Card::make()->bordered()
     */
    public function bordered(): static
    {
        return $this->variant('bordered');
    }

    /**
     * 阴影变体
     * @return static
     * @ux-example Card::make()->shadow()
     */
    public function shadow(): static
    {
        return $this->variant('shadow');
    }

    /**
     * 扁平变体
     * @return static
     * @ux-example Card::make()->flat()
     */
    public function flat(): static
    {
        return $this->variant('flat');
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-card');
        $el->class("ux-card-{$this->variant}");

        if ($this->image && $this->imagePosition === 'top') {
            $el->child(
                Element::make('img')
                    ->class('ux-card-img-top')
                    ->attr('src', $this->image)
                    ->attr('alt', 'Card image')
            );
        }

        if ($this->header || $this->title) {
            $headerEl = Element::make('div')->class('ux-card-header');
            if ($this->header) {
                $headerEl->child($this->resolveChild($this->header));
            } else {
                $headerEl->child(Element::make('h3')->class('ux-card-title')->text($this->title));
                if ($this->subtitle) {
                    $headerEl->child(Element::make('p')->class('ux-card-subtitle')->text($this->subtitle));
                }
            }
            $el->child($headerEl);
        }

        $bodyEl = Element::make('div')->class('ux-card-body');
        $this->appendChildren($bodyEl);
        $el->child($bodyEl);

        if ($this->footer) {
            $el->child(
                Element::make('div')
                    ->class('ux-card-footer')
                    ->child($this->resolveChild($this->footer))
            );
        }

        if ($this->image && $this->imagePosition === 'bottom') {
            $el->child(
                Element::make('img')
                    ->class('ux-card-img-bottom')
                    ->attr('src', $this->image)
                    ->attr('alt', 'Card image')
            );
        }

        return $el;
    }
}
