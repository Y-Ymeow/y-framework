<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UI\Pagination;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DataList extends UXComponent
{
    protected array $dataSource = [];
    protected ?\Closure $renderItem = null;
    protected string $variant = 'default';
    protected string $size = 'md';
    protected bool $bordered = false;
    protected bool $split = true;
    protected ?string $emptyText = null;
    protected mixed $header = null;
    protected mixed $footer = null;
    protected ?string $title = null;
    protected ?Pagination $pagination = null;

    protected ?string $fragmentName = null;
    protected ?string $itemAction = null;
    protected string $itemActionEvent = 'click';
    protected ?string $pageAction = null;

    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    public function rows(array $data): static
    {
        return $this->dataSource($data);
    }

    public function renderItem(\Closure $callback): static
    {
        $this->renderItem = $callback;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
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

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    public function split(bool $split = true): static
    {
        $this->split = $split;
        return $this;
    }

    public function emptyText(string $text): static
    {
        $this->emptyText = $text;
        return $this;
    }

    public function header(mixed $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function pagination(int $total, int $current = 1, int $perPage = 15, string $baseUrl = ''): static
    {
        $this->pagination = new Pagination();
        $this->pagination->total($total)->current($current)->perPage($perPage);
        if ($baseUrl) {
            $this->pagination->baseUrl($baseUrl);
        }
        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    public function itemAction(string $action, string $event = 'click'): static
    {
        $this->itemAction = $action;
        $this->itemActionEvent = $event;
        return $this;
    }

    public function pageAction(string $action): static
    {
        $this->pageAction = $action;
        return $this;
    }

    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);
        $wrapper->class('ux-data-list-wrapper');

        if ($this->title || $this->header) {
            $headerEl = Element::make('div')->class('ux-data-list-header');
            if ($this->title) {
                $headerEl->child(Element::make('div')->class('ux-data-list-title')->text($this->title));
            }
            if ($this->header) {
                $headerEl->child(Element::make('div')->class('ux-data-list-header-extra')->child($this->resolveChild($this->header)));
            }
            $wrapper->child($headerEl);
        }

        $listEl = Element::make('ul')->class('ux-data-list');
        $listEl->class("ux-data-list-{$this->variant}");
        $listEl->class("ux-data-list-{$this->size}");

        if ($this->bordered) {
            $listEl->class('ux-data-list-bordered');
        }
        if ($this->split) {
            $listEl->class('ux-data-list-split');
        }

        if ($this->fragmentName) {
            $listEl->liveFragment($this->fragmentName);
        }

        if (empty($this->dataSource)) {
            $emptyText = $this->emptyText ?? '暂无数据';
            $li = Element::make('li')->class('ux-data-list-item ux-data-list-empty');
            $li->child(
                Element::make('div')->class('ux-data-list-empty-content')->text($emptyText)
            );
            $listEl->child($li);
        } else {
            foreach ($this->dataSource as $index => $item) {
                $li = Element::make('li')->class('ux-data-list-item');
                $li->data('index', (string)$index);

                $itemAction = $this->itemAction ?? $this->liveAction;
                if ($itemAction) {
                    $li->liveAction($itemAction, $this->itemActionEvent);
                    $li->data('action-params', json_encode(['index' => $index], JSON_UNESCAPED_UNICODE));
                }

                if ($this->renderItem) {
                    $rendered = ($this->renderItem)($item, $index);
                    if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                        $li->child($this->resolveChild($rendered));
                    } elseif (is_string($rendered)) {
                        $li->html($rendered);
                    }
                } else {
                    $li->text((string)$item);
                }

                $listEl->child($li);
            }
        }

        $wrapper->child($listEl);

        if ($this->footer) {
            $wrapper->child(
                Element::make('div')->class('ux-data-list-footer')->child($this->resolveChild($this->footer))
            );
        }

        if ($this->pagination) {
            $pageAction = $this->pageAction ?? $this->liveAction;
            if ($pageAction) {
                $this->pagination->liveAction($pageAction, $this->liveEvent ?? 'click');
            }
            $wrapper->child($this->pagination->toElement());
        }

        return $wrapper;
    }
}
