<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Pagination extends UXComponent
{
    protected int $total = 0;
    protected int $current = 1;
    protected int $perPage = 15;
    protected string $baseUrl = '';
    protected string $pageParam = 'page';
    protected array $perPageOptions = [];
    protected ?string $perPageAction = null;
    protected string $perPageParam = 'perPage';
    protected bool $showPerPage = false;
    protected int $perPageTotal = 0;

    public function total(int $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function current(int $current): static
    {
        $this->current = $current;
        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function baseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function perPageOptions(array $options): static
    {
        $this->perPageOptions = $options;
        return $this;
    }

    public function perPageAction(string $action): static
    {
        $this->perPageAction = $action;
        return $this;
    }

    public function showPerPage(int $total = 0, int $perPage = 15, int $current = 1): static
    {
        $this->showPerPage = true;
        $this->perPageTotal = $total;
        $this->perPage = $perPage;
        $this->current = $current;
        return $this;
    }

    protected function toElement(): Element
    {
        $lastPage = (int)ceil($this->total / $this->perPage);
        
        $navEl = new Element('nav');
        $this->buildElement($navEl);
        $navEl->class('ux-pagination');

        if ($lastPage > 1) {
            $listEl = Element::make('ul')->class('ux-pagination-list');

            // Previous
            $prevItemEl = Element::make('li')->class('ux-pagination-item');
            if ($this->current <= 1) {
                $prevItemEl->class('disabled');
            }
            $prevItemEl->child($this->renderLinkElement($this->current - 1, '«'));
            $listEl->child($prevItemEl);

            // Page Numbers
            for ($i = 1; $i <= $lastPage; $i++) {
                if ($i === 1 || $i === $lastPage || ($i >= $this->current - 2 && $i <= $this->current + 2)) {
                    $itemEl = Element::make('li')->class('ux-pagination-item');
                    if ($i === $this->current) {
                        $itemEl->class('active');
                    }
                    $itemEl->child($this->renderLinkElement($i, (string)$i));
                    $listEl->child($itemEl);
                } elseif ($i === 2 || $i === $lastPage - 1) {
                    $listEl->child(Element::make('li')->class('ux-pagination-item ellipsis')->text('...'));
                }
            }

            // Next
            $nextItemEl = Element::make('li')->class('ux-pagination-item');
            if ($this->current >= $lastPage) {
                $nextItemEl->class('disabled');
            }
            $nextItemEl->child($this->renderLinkElement($this->current + 1, '»'));
            $listEl->child($nextItemEl);

            $navEl->child($listEl);
        } else {
            // 如果只有一页，且不显示每页条数，则返回空
            if (!$this->showPerPage && empty($this->perPageOptions)) {
                return new Element('span');
            }
            // 否则显示一个空的列表占位，或者只显示统计信息
            $navEl->child(Element::make('div')->class('ux-pagination-info')->text("共 {$this->total} 条记录"));
        }

        if (!empty($this->perPageOptions) || $this->showPerPage) {
            $navEl->child($this->buildPerPageSelector());
        }

        return $navEl;
    }

    protected function buildPerPageSelector(): Element
    {
        $wrapper = Element::make('div')->class('ux-pagination-perpage');

        $select = Element::make('select')->class('ux-pagination-perpage-select');

        foreach ($this->perPageOptions as $option) {
            $opt = Element::make('option')
                ->attr('value', (string)$option)
                ->text((string)$option);
            if ($option === $this->perPage) {
                $opt->attr('selected', 'selected');
            }
            $select->child($opt);
        }

        $action = $this->perPageAction ?? $this->liveAction;
        if ($action) {
            $select->liveAction($action, 'change');
            $select->data('action-params', json_encode([$this->perPageParam => '__value__']));
        }

        $wrapper->child(Element::make('span')->class('ux-pagination-perpage-label')->text('每页'));
        $wrapper->child($select);
        $wrapper->child(Element::make('span')->class('ux-pagination-perpage-suffix')->text('条'));

        return $wrapper;
    }

    protected function renderLinkElement(int $page, string $label): Element
    {
        if ($this->liveAction) {
            return Element::make('button')
                ->attr('type', 'button')
                ->class('ux-pagination-link')
                ->liveAction($this->liveAction, $this->liveEvent ?? 'click')
                ->data('action-params', json_encode([$this->pageParam => $page]))
                ->text($label);
        }

        $url = $this->baseUrl;
        $separator = str_contains($url, '?') ? '&' : '?';
        $fullUrl = $url . $separator . $this->pageParam . '=' . $page;

        return Element::make('a')
            ->class('ux-pagination-link')
            ->attr('href', $fullUrl)
            ->data('page', (string) $page)
            ->text($label);
    }
}
