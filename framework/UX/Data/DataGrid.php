<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\Navigation\Pagination;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 数据表格
 *
 * 用于展示结构化表格数据，支持列定义、排序、筛选、分页、行操作、单元格编辑。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DataGrid::make()->columns($columns)->rows($users)->sortable()->pagination()
 * @ux-example DataGrid::make()->columns(['name' => '姓名', 'email' => '邮箱'])->rows($data)->searchable()
 * @ux-js-component data-grid.js
 * @ux-css data-grid.css
 */
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

    /**
     * 设置数据源
     * @param array $data 数据数组
     * @return static
     */
    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    /**
     * 设置数据行（别名）
     * @param array $data 数据数组
     * @return static
     */
    public function rows(array $data): static
    {
        return $this->dataSource($data);
    }

    /**
     * 自定义每行渲染回调
     * @param \Closure $callback 回调函数，接收 ($item, $index)
     * @return static
     */
    public function renderItem(\Closure $callback): static
    {
        $this->renderItem = $callback;
        return $this;
    }

    /**
     * 设置列数
     * @param int $cols 列数（至少 1）
     * @return static
     * @ux-default 3
     */
    public function cols(int $cols): static
    {
        $this->cols = max(1, $cols);
        return $this;
    }

    /**
     * 设置网格间距（单位：rem）
     * @param int $gap 间距（4 = 1rem）
     * @return static
     * @ux-default 4
     */
    public function gap(int $gap): static
    {
        $this->gap = $gap;
        return $this;
    }

    /**
     * 设置变体
     * @param string $variant 变体：default
     * @return static
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置空数据提示文字
     * @param string $text 提示文字
     * @return static
     */
    public function emptyText(string $text): static
    {
        $this->emptyText = $text;
        return $this;
    }

    /**
     * 设置自定义头部内容
     * @param mixed $header 自定义内容（Element 或组件）
     * @return static
     */
    public function header(mixed $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 设置自定义底部内容
     * @param mixed $footer 自定义内容（Element 或组件）
     * @return static
     */
    public function footer(mixed $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * 设置网格标题
     * @param string $title 标题
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置分页
     * @param int $total 总条数
     * @param int $current 当前页
     * @param int $perPage 每页条数
     * @param string $baseUrl 分页链接基础 URL
     * @return static
     * @ux-example DataGrid::make()->rows($data)->pagination(100, 1, 15)
     */
    public function pagination(int $total, int $current = 1, int $perPage = 15, string $baseUrl = ''): static
    {
        $this->pagination = new Pagination();
        $this->pagination->total($total)->current($current)->perPage($perPage);
        if ($baseUrl) {
            $this->pagination->baseUrl($baseUrl);
        }
        return $this;
    }

    /**
     * 设置分片名称（用于 Live 局部更新）
     * @param string $name 分片名
     * @return static
     */
    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    /**
     * 设置行点击动作
     * @param string $action LiveAction 名称
     * @param string $event 触发事件
     * @return static
     * @ux-default event='click'
     */
    public function itemAction(string $action, string $event = 'click'): static
    {
        $this->itemAction = $action;
        $this->itemActionEvent = $event;
        return $this;
    }

    /**
     * 设置分页动作
     * @param string $action LiveAction 名称
     * @return static
     */
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
            $emptyText = $this->emptyText ?? t('ux:data-grid.empty_data');
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
                    $cellEl->liveAction($itemAction, $this->itemActionEvent, ['index' => $index]);
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
