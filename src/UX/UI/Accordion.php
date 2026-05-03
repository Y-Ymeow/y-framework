<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 手风琴
 *
 * 用于展示可折叠的内容面板组，支持多面板、标题、内容、展开/折叠控制。
 *
 * @ux-category UI
 * @ux-since 1.0.0
 * @ux-example Accordion::make()->panel('面板1', '内容1')->panel('面板2', '内容2')
 * @ux-example Accordion::make()->panel('标题1', $view1)->panel('标题2', $view2)->allowMultiple()
 * @ux-js-component accordion.js
 * @ux-css accordion.css
 */
class Accordion extends UXComponent
{
    protected array $items = [];
    protected bool $multiple = false;
    protected string $variant = 'default';
    protected bool $dark = false;

    /**
     * 添加面板项
     * @param mixed $title 标题（支持字符串或组件）
     * @param mixed $content 内容（支持字符串或组件）
     * @param string|null $id 面板 ID（自动生成）
     * @param bool $open 是否默认展开
     * @return static
     * @ux-example Accordion::make()->item('标题', '内容')->item('另一个', '内容2')
     */
    public function item(mixed $title, mixed $content, ?string $id = null, bool $open = false): static
    {
        $id = $id ?? 'accordion-item-' . count($this->items);
        $this->items[] = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'open' => $open,
        ];
        return $this;
    }

    /**
     * 允许多个面板同时展开
     * @param bool $multiple 是否允许多开
     * @return static
     * @ux-default false
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * 设置变体样式
     * @param string $variant 变体名
     * @return static
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 启用暗色主题
     * @param bool $dark 是否暗色
     * @return static
     * @ux-default false
     */
    public function dark(bool $dark = true): static
    {
        $this->dark = $dark;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-accordion');
        $el->class("ux-accordion-{$this->variant}");

        if ($this->dark) {
            $el->class('ux-accordion-dark');
        }

        if ($this->multiple) {
            $el->data('accordion-multiple', 'true');
        }

        foreach ($this->items as $item) {
            $isOpen = $item['open'];

            $itemEl = Element::make('div')
                ->class('ux-accordion-item')
                ->id($item['id']);
            if ($isOpen) {
                $itemEl->class('open');
            }

            // Header
            $headerEl = Element::make('button')
                ->class('ux-accordion-header')
                ->attr('type', 'button')
                ->attr('aria-expanded', $isOpen ? 'true' : 'false');

            if ($this->liveAction) {
                $headerEl->liveAction($this->liveAction, $this->liveEvent ?? 'click');
                $headerEl->data('action-params', json_encode(['id' => $item['id'], 'open' => !$isOpen]));
            }

            $titleEl = Element::make('span')->class('ux-accordion-title');
            $title = $item['title'];
            if (is_string($title)) {
                $titleEl->html($title);
            } else {
                $titleEl->child($this->resolveChild($title));
            }
            
            $headerEl->child($titleEl);
            $headerEl->child(Element::make('span')->class('ux-accordion-icon'));

            $itemEl->child($headerEl);

            // Content
            $collapseEl = Element::make('div')->class('ux-accordion-collapse');
            if ($isOpen) {
                $collapseEl->class('show');
            }

            $bodyEl = Element::make('div')->class('ux-accordion-body');
            $content = $item['content'];
            if (is_string($content)) {
                $bodyEl->html($content);
            } else {
                $bodyEl->child($this->resolveChild($content));
            }

            $collapseEl->child($bodyEl);
            $itemEl->child($collapseEl);

            $el->child($itemEl);
        }

        return $el;
    }
}
