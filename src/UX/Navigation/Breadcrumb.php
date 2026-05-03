<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 面包屑
 *
 * 用于展示页面层级导航，支持自定义分隔符、链接/纯文本项。
 *
 * @ux-category Navigation
 * @ux-since 1.0.0
 * @ux-example Breadcrumb::make()->item('首页', '/')->item('分类', '/cat')->item('当前页')
 * @ux-example Breadcrumb::make()->item('Home')->item('Products')->item('Details')->separator('>')
 * @ux-js-component —
 * @ux-css breadcrumb.css
 */
class Breadcrumb extends UXComponent
{
    protected array $items = [];
    protected string $separator = '/';

    /**
     * 添加面包屑项
     * @param string $label 显示文字
     * @param string|null $link 链接地址（最后一项通常为 null）
     * @return static
     * @ux-example Breadcrumb::make()->item('首页', '/')->item('分类', '/cat')->item('当前页')
     */
    public function item(string $label, ?string $link = null): static
    {
        $this->items[] = [
            'label' => $label,
            'link' => $link,
        ];
        return $this;
    }

    /**
     * 设置分隔符
     * @param string $separator 分隔符，默认 '/'
     * @return static
     * @ux-default '/'
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $navEl = new Element('nav');
        $this->buildElement($navEl);

        $navEl->class('ux-breadcrumb');
        $navEl->attr('aria-label', 'breadcrumb');

        $listEl = Element::make('ol')->class('ux-breadcrumb-list');

        $count = count($this->items);
        foreach ($this->items as $index => $item) {
            $isLast = ($index === $count - 1);

            $itemEl = Element::make('li')->class('ux-breadcrumb-item');
            if ($isLast) {
                $itemEl->class('active');
                $itemEl->attr('aria-current', 'page');
            }

            if ($item['link'] && !$isLast) {
                $itemEl->child(
                    Element::make('a')
                        ->class('ux-breadcrumb-link')
                        ->attr('href', $item['link'])
                        ->text($item['label'])
                );
            } else {
                $itemEl->child(
                    Element::make('span')
                        ->class('ux-breadcrumb-text')
                        ->text($item['label'])
                );
            }

            if (!$isLast) {
                $itemEl->child(
                    Element::make('span')
                        ->class('ux-breadcrumb-separator')
                        ->text($this->separator)
                );
            }

            $listEl->child($itemEl);
        }

        $navEl->child($listEl);

        return $navEl;
    }
}
