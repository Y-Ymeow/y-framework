<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UI\Pagination;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DataGrid extends UXComponent
{
    protected array $dataSource = [];
    protected ?\Closure $renderItem = null;
    protected int $cols = 3;
    protected int $gap = 4;
    protected string $variant = 'default';
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

    public function cols(int $cols): static
    {
        $this->cols = max(1, $cols);
        return $this;
    }

    public function gap(int $gap): static
    {
        $this->gap = $gap;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
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
        $wrapper->class('ux-data-grid-wrapper');

        if ($this->title || $this->header) {
            $headerEl = Element::make('div')->class('ux-data-grid-header');
            if ($this->title) {
                $headerEl->child(Element::make('div')->class('ux-data-grid-title')->text($this->title));
            }
            if ($this->header) {
                $headerEl->child(Element::make('div')->class('ux-data-grid-header-extra')->child($this->resolveChild($this->header)));
            }
            $wrapper->child($headerEl);
        }

        $gridEl = Element::make('div')->class('ux-data-grid');
        $gridEl->class("ux-data-grid-{$this->variant}");
        $gridEl->class("ux-data-grid-cols-{$this->cols}");
        $gapRem = $this->gap * 0.25;
        $gridEl->style("gap:{$gapRem}rem");

        if ($this->fragmentName) {
            $gridEl->liveFragment($this->fragmentName);
        }

        if (empty($this->dataSource)) {
            $emptyText = $this->emptyText ?? t('ux.empty_data');
            $gridEl->child(
                Element::make('div')
                    ->class('ux-data-grid-empty')
                    ->style('grid-column:1/-1')
                    ->text($emptyText)
            );
        } else {
            foreach ($this->dataSource as $index => $item) {
                $cellEl = Element::make('div')->class('ux-data-grid-item');
                $cellEl->data('index', (string)$index);

                $itemAction = $this->itemAction ?? $this->liveAction;
                if ($itemAction) {
                    $cellEl->liveAction($itemAction, $this->itemActionEvent);
                    $cellEl->data('action-params', json_encode(['index' => $index], JSON_UNESCAPED_UNICODE));
                }

                if ($this->renderItem) {
                    $rendered = ($this->renderItem)($item, $index);
                    if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                        $cellEl->child($this->resolveChild($rendered));
                    } elseif (is_string($rendered)) {
                        $cellEl->html($rendered);
                    }
                } else {
                    $cellEl->text((string)$item);
                }

                $gridEl->child($cellEl);
            }
        }

        $wrapper->child($gridEl);

        if ($this->footer) {
            $wrapper->child(
                Element::make('div')->class('ux-data-grid-footer')->child($this->resolveChild($this->footer))
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
