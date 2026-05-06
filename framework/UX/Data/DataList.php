<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\Navigation\Pagination;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 数据列表
 *
 * 用于展示结构化数据列表，支持列定义、排序、分页、行操作。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DataList::make()->columns(['name' => '姓名', 'age' => '年龄'])->rows($users)
 * @ux-example DataList::make()->columns($cols)->rows($data)->sortable()->pagination()
 * @ux-js-component data-list.js
 * @ux-css data-list.css
 */
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

    /**
     * 设置数据源
     * @param array $data 数据数组
     * @return static
     * @ux-example DataList::make()->dataSource($users)
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
     * @ux-example DataList::make()->rows($data)->renderItem(fn($item) => Text::make()->text($item['name']))
     */
    public function renderItem(\Closure $callback): static
    {
        $this->renderItem = $callback;
        return $this;
    }

    /**
     * 设置列表变体
     * @param string $variant 变体：default/striped
     * @return static
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置列表尺寸
     * @param string $size 尺寸：sm/md/lg
     * @return static
     * @ux-default 'md'
     */
    public function size(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 小尺寸
     * @return static
     * @ux-example DataList::make()->rows($data)->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example DataList::make()->rows($data)->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置是否带边框
     * @param bool $bordered 是否带边框
     * @return static
     * @ux-default false
     */
    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    /**
     * 设置是否分隔线样式
     * @param bool $split 是否分隔线
     * @return static
     * @ux-default true
     */
    public function split(bool $split = true): static
    {
        $this->split = $split;
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
     * 设置列表标题
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
     * @ux-example DataList::make()->rows($data)->pagination(100, 1, 15)
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
            $emptyText = $this->emptyText ?? t('ux.empty_data');
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
                    $li->liveAction($itemAction, $this->itemActionEvent ?? 'click', ['index' => $index]);
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
