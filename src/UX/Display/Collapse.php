<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Collapse extends UXComponent
{
    protected string $title = '';
    protected bool $open = false;
    protected bool $disabled = false;
    protected ?string $icon = null;
    protected ?string $action = null;

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function open(bool $open = true): static
    {
        $this->open = $open;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
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

        $el->class('ux-collapse');
        if ($this->open) {
            $el->class('ux-collapse-open');
        }
        if ($this->disabled) {
            $el->class('ux-collapse-disabled');
        }

        if ($this->action) {
            $el->data('collapse-action', $this->action);
        }

        // 头部
        $headerEl = Element::make('div')->class('ux-collapse-header');

        if ($this->icon) {
            $iconClass = str_starts_with($this->icon, 'bi-') ? $this->icon : 'bi-' . $this->icon;
            $headerEl->child(
                Element::make('i')->class($iconClass)->class('ux-collapse-icon')
            );
        }

        $headerEl->child(
            Element::make('span')->class('ux-collapse-title')->text($this->title)
        );

        // 展开/折叠图标
        $arrowEl = Element::make('span')
            ->class('ux-collapse-arrow')
            ->html('<i class="bi bi-chevron-right"></i>');
        $headerEl->child($arrowEl);

        $el->child($headerEl);

        // 内容区域
        $contentEl = Element::make('div')->class('ux-collapse-content');
        $this->appendChildren($contentEl);
        $el->child($contentEl);

        return $el;
    }
}
