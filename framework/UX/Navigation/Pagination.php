<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 分页
 *
 * 用于数据分页导航，支持页码列表、上一页/下一页、每页条数选择、Live 集成。
 *
 * @ux-category Navigation
 * @ux-since 1.0.0
 * @ux-example Pagination::make()->total(100)->current(2)->perPage(10)
 * @ux-example Pagination::make()->total($total)->perPageOptions([10, 20, 50])->showPerPage()
 * @ux-js-component pagination.js
 * @ux-css pagination.css
 */
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

    /**
     * 设置数据总量
     * @param int $total 总记录数
     * @return static
     */
    public function total(int $total): static
    {
        $this->total = $total;
        return $this;
    }

    /**
     * 设置当前页码
     * @param int $current 当前页码（从 1 开始）
     * @return static
     * @ux-default 1
     */
    public function current(int $current): static
    {
        $this->current = $current;
        return $this;
    }

    /**
     * 设置每页显示条数
     * @param int $perPage 每页条数
     * @return static
     * @ux-default 15
     */
    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * 设置分页基础 URL
     * @param string $baseUrl 基础 URL
     * @return static
     */
    public function baseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * 设置每页条数可选值
     * @param array $options 可选值列表，如 [10, 20, 50]
     * @return static
     */
    public function perPageOptions(array $options): static
    {
        $this->perPageOptions = $options;
        return $this;
    }

    /**
     * 设置每页条数变更的 LiveAction
     * @param string $action Action 方法名
     * @return static
     */
    public function perPageAction(string $action): static
    {
        $this->perPageAction = $action;
        return $this;
    }

    /**
     * 启用每页条数选择器并设置统计信息
     * @param int $total 总记录数
     * @param int $perPage 每页条数
     * @param int $current 当前页码
     * @return static
     */
    public function showPerPage(int $total = 0, int $perPage = 15, int $current = 1): static
    {
        $this->showPerPage = true;
        $this->perPageTotal = $total;
        $this->perPage = $perPage;
        $this->current = $current;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        if ($this->perPage <= 0) {
            $this->perPage = 15; // Default fallback
        }
        $lastPage = (int)ceil($this->total / $this->perPage);

        $navEl = new Element('nav');
        $this->buildElement($navEl, ['liveAction', 'isStream', 'uxModel']);
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
            $navEl->child(Element::make('div')->class('ux-pagination-info')->intl('ux:pagination.showing', ['from' => 1, 'to' => $this->total, 'total' => $this->total]));
        }

        if (!empty($this->perPageOptions) || $this->showPerPage) {
            $navEl->child($this->buildPerPageSelector());
        }

        return $navEl;
    }

    protected function buildPerPageSelector(): Element
    {
        $wrapper = Element::make('div')->class('ux-pagination-perpage')->state(["page" => $this->perPage]);

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
            $select->liveAction($action, 'change', "{ $this->perPageParam: page }")->bindModel('page');
        }

        $wrapper->child(Element::make('span')->class('ux-pagination-perpage-label')->intl('ux:pagination.per_page'));
        $wrapper->child($select);
        $wrapper->child(Element::make('span')->class('ux-pagination-perpage-suffix')->intl('ux:pagination.items'));

        return $wrapper;
    }

    protected function renderLinkElement(int $page, string $label): Element
    {
        if ($this->liveAction) {
            return Element::make('button')
                ->attr('type', 'button')
                ->class('ux-pagination-link')
                ->liveAction($this->liveAction, $this->liveEvent ?? 'click', [$this->pageParam => $page])
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
