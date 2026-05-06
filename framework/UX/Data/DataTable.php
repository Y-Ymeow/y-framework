<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\Navigation\Pagination;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 数据表格
 *
 * 用于展示结构化表格数据，支持列定义、排序、筛选、分页、行操作、单元格编辑、批量操作、Live 集成。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DataTable::make()->columns($columns)->rows($users)->sortable()->pagination(100)
 * @ux-example DataTable::make()->columns(['name' => '姓名', 'email' => '邮箱'])->rows($data)->searchable()->selectable()
 * @ux-example DataTable::make()->columns($cols)->rows($items)->actions(['edit' => 'editRow', 'delete' => 'deleteRow'])
 * @ux-js-component data-table.js
 * @ux-css data-table.css
 */
class DataTable extends UXComponent
{
    protected array $columns = [];
    protected array $dataSource = [];
    protected string $rowKey = 'id';
    protected string $size = 'md';
    protected string $variant = 'default';
    protected bool $striped = false;
    protected bool $bordered = false;
    protected bool $hoverable = true;
    protected bool $selectable = false;
    protected ?string $emptyText = null;
    protected mixed $header = null;
    protected mixed $footer = null;
    protected ?string $title = null;
    protected ?Pagination $pagination = null;
    protected array $rowAttrs = [];
    protected ?\Closure $rowCallback = null;

    protected ?string $sortField = null;
    protected string $sortDirection = 'asc';
    protected ?string $fragmentName = null;
    protected ?string $sortAction = null;
    protected ?string $pageAction = null;
    protected ?string $selectAction = null;
    protected ?string $rowAction = null;
    protected string $rowActionEvent = 'click';

    protected array $actions = [];
    protected bool $searchable = false;
    protected ?string $searchAction = null;
    protected ?string $searchValue = null;
    protected ?string $searchPlaceholder = null;
    protected array $batchActions = [];
    protected ?string $batchAction = null;
    protected array $perPageOptions = [10, 15, 30, 50, 100];
    protected ?string $perPageAction = null;
    protected ?\Closure $tooltipCallback = null;
    protected ?\Closure $rowActionsCallback = null;
    protected bool $showPerPage = false;
    protected int $total = 0;
    protected int $perPage = 15;
    protected int $page = 1;

    // 行内编辑
    protected bool $editable = false;
    protected array $editableColumns = [];
    protected ?string $editAction = null;
    protected string $editType = 'input';

    /**
     * 添加一列
     * @param string $dataKey 数据键名
     * @param string $title 列标题
     * @param \Closure|null $render 自定义渲染回调
     * @param array $options 列选项（width, align, sortable, fixed, visible, tooltip, searchable 等）
     * @return static
     * @ux-example DataTable::make()->column('name', '姓名')->column('email', '邮箱', null, ['sortable' => true])
     */
    public function column(string $dataKey, string|array $title, ?\Closure $render = null, array $options = []): static
    {
        $this->columns[] = array_merge([
            'dataKey' => $dataKey,
            'title' => $title,
            'render' => $render,
            'width' => null,
            'align' => null,
            'sortable' => false,
            'fixed' => null,
            'visible' => true,
            'tooltip' => null,
            'searchable' => false,
            'searchType' => 'like',
            'searchOptions' => null,
        ], $options);
        return $this;
    }

    /**
     * 使用 DataTableColumn 对象添加列（链式）
     * @param DataTableColumn $column 列对象
     * @return static
     * @ux-example DataTable::make()->addColumn(DataTableColumn::make('name', '姓名')->sortable()->alignCenter())
     */
    public function addColumn(DataTableColumn $column): static
    {
        $this->columns[] = $column->toArray();
        return $this;
    }

    /**
     * 获取可搜索的列
     * @return array
     */
    public function getSearchableColumns(): array
    {
        return array_filter($this->columns, fn($col) => !empty($col['searchable']));
    }

    /**
     * 批量设置列
     * @param array $columns 列配置数组
     * @return static
     * @ux-example DataTable::make()->columns(['name' => '姓名', 'email' => '邮箱'])
     */
    public function columns(array $columns): static
    {
        foreach ($columns as $col) {
            if ($col instanceof self) {
                continue;
            }
            $this->column(
                $col['dataKey'] ?? $col['key'] ?? '',
                $col['title'] ?? $col['label'] ?? '',
                $col['render'] ?? null,
                $col
            );
        }
        return $this;
    }

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
     * 设置行键名
     * @param string $key 键名
     * @return static
     * @ux-default 'id'
     */
    public function rowKey(string $key): static
    {
        $this->rowKey = $key;
        return $this;
    }

    /**
     * 设置表格尺寸
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
     * @ux-example DataTable::make()->rows($data)->sm()
     */
    public function sm(): static
    {
        return $this->size('sm');
    }

    /**
     * 大尺寸
     * @return static
     * @ux-example DataTable::make()->rows($data)->lg()
     */
    public function lg(): static
    {
        return $this->size('lg');
    }

    /**
     * 设置斑马纹
     * @param bool $striped 是否斑马纹
     * @return static
     * @ux-default false
     */
    public function striped(bool $striped = true): static
    {
        $this->striped = $striped;
        return $this;
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
     * 设置是否悬停高亮
     * @param bool $hoverable 是否悬停高亮
     * @return static
     * @ux-default true
     */
    public function hoverable(bool $hoverable = true): static
    {
        $this->hoverable = $hoverable;
        return $this;
    }

    /**
     * 设置是否可选（单选/多选）
     * @param bool $selectable 是否可选
     * @return static
     * @ux-default false
     */
    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;
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
     * 设置表格标题
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
     * @ux-example DataTable::make()->rows($data)->pagination(100, 1, 15)
     */
    public function pagination(int $total, int $current = 1, int $perPage = 15, string $baseUrl = ''): static
    {
        $this->pagination = new Pagination();
        $this->pagination->class("px-6! py-4!");
        $this->pagination->total($total)->current($current)->perPage($perPage);
        if ($baseUrl) {
            $this->pagination->baseUrl($baseUrl);
        }
        return $this;
    }

    /**
     * 设置自定义分页组件
     * @param Pagination $pagination 分页组件
     * @return static
     */
    public function paginationComponent(Pagination $pagination): static
    {
        $this->pagination = $pagination;
        return $this;
    }

    /**
     * 设置行属性
     * @param string $key 属性名
     * @param string $value 属性值
     * @return static
     */
    public function rowAttr(string $key, string $value): static
    {
        $this->rowAttrs[$key] = $value;
        return $this;
    }

    /**
     * 设置行回调（动态添加类名等）
     * @param \Closure $callback 回调函数，接收 ($row, $index) 返回字符串或数组
     * @return static
     */
    public function rowCallback(\Closure $callback): static
    {
        $this->rowCallback = $callback;
        return $this;
    }

    /**
     * 设置当前排序字段
     * @param string|null $field 字段名
     * @return static
     */
    public function sortField(?string $field): static
    {
        $this->sortField = $field;
        return $this;
    }

    /**
     * 设置排序方向
     * @param string $direction 方向：asc/desc
     * @return static
     * @ux-default 'asc'
     */
    public function sortDirection(string $direction): static
    {
        $this->sortDirection = $direction;
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
     * 设置排序动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function sortAction(string $action): static
    {
        $this->sortAction = $action;
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

    /**
     * 设置选择动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function selectAction(string $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    /**
     * 注册单个动作
     * @param string $name 动作名
     * @param string $action LiveAction 名称
     * @param string $event 触发事件
     * @param array $config 额外配置（label, class, icon, params 等）
     * @return static
     */
    public function registerAction(string $name, string $action, string $event = 'click', array $config = []): static
    {
        $this->actions[$name] = array_merge([
            'action' => $action,
            'event' => $event,
        ], $config);
        return $this;
    }

    /**
     * 批量注册动作
     * @param array $actions 动作配置数组
     * @return static
     */
    public function actions(array $actions): static
    {
        foreach ($actions as $name => $config) {
            if (is_string($config)) {
                $this->registerAction($name, $config);
            } elseif (is_array($config)) {
                $action = $config['action'] ?? $config['handler'] ?? '';
                $event = $config['event'] ?? 'click';
                $this->registerAction($name, $action, $event, $config);
            }
        }
        return $this;
    }

    /**
     * 设置是否可搜索
     * @param bool $searchable 是否可搜索
     * @return static
     * @ux-default false
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * 设置搜索动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function searchAction(string $action): static
    {
        $this->searchAction = $action;
        return $this;
    }

    /**
     * 设置搜索值
     * @param string|null $value 搜索值
     * @return static
     */
    public function searchValue(?string $value): static
    {
        $this->searchValue = $value;
        return $this;
    }

    /**
     * 设置搜索框占位符
     * @param string $placeholder 占位符
     * @return static
     */
    public function searchPlaceholder(string $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;
        return $this;
    }

    /**
     * 设置批量操作列表
     * @param array $actions 批量操作配置数组
     * @return static
     */
    public function batchActions(array $actions): static
    {
        $this->batchActions = $actions;
        return $this;
    }

    /**
     * 设置批量操作动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function batchAction(string $action): static
    {
        $this->batchAction = $action;
        return $this;
    }

    /**
     * 设置每页条数选项
     * @param array $options 选项数组
     * @return static
     * @ux-default [10, 15, 30, 50, 100]
     */
    public function perPageOptions(array $options): static
    {
        $this->perPageOptions = $options;
        return $this;
    }

    /**
     * 设置每页条数动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function perPageAction(string $action): static
    {
        $this->perPageAction = $action;
        return $this;
    }

    /**
     * 显示每页条数选择器
     * @param bool $show 是否显示
     * @param int $total 总条数
     * @param int $perPage 每页条数
     * @param int $page 当前页
     * @return static
     * @ux-default false
     */
    public function showPerPage(bool $show = true, int $total = 0, int $perPage = 15, int $page = 1): static
    {
        $this->showPerPage = $show;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->page = $page;
        return $this;
    }

    /**
     * 设置提示回调
     * @param \Closure $callback 回调函数，接收 ($row, $index) 返回提示文字
     * @return static
     */
    public function tooltip(?\Closure $callback): static
    {
        $this->tooltipCallback = $callback;
        return $this;
    }

    /**
     * 注册行操作组件
     * 闭包接收 ($row, $rowKey, $rowIndex) 返回组件数组
     * @param \Closure $callback 回调函数
     * @return static
     * @ux-example DataTable::make()->rows($data)->rowActions(fn($row) => [Button::make()->label('编辑')->liveAction('edit', $row['id'])])
     */
    public function rowActions(\Closure $callback): static
    {
        $this->rowActionsCallback = $callback;
        return $this;
    }

    /**
     * 启用行内编辑
     * @param array $columns 可编辑的列配置 ['column_key' => ['type' => 'input', 'rules' => []], ...]
     * @param string $action 保存编辑的 LiveAction 名称
     * @return static
     * @ux-example DataTable::make()->rows($data)->editable(['name' => ['type' => 'input']], 'saveEdit')
     */
    public function editable(array $columns = [], string $action = 'saveEdit'): static
    {
        $this->editable = true;
        $this->editableColumns = $columns;
        $this->editAction = $action;
        return $this;
    }

    /**
     * 设置编辑类型
     * @param string $type 编辑类型：input|textarea|select|switch
     * @return static
     * @ux-default 'input'
     */
    public function editType(string $type): static
    {
        $this->editType = $type;
        return $this;
    }

    /**
     * 获取可编辑的列配置
     * @return array
     */
    public function getEditableColumns(): array
    {
        return $this->editableColumns;
    }

    /**
     * 检查列是否可编辑
     * @param string $columnKey 列键名
     * @return bool
     */
    public function isColumnEditable(string $columnKey): bool
    {
        return isset($this->editableColumns[$columnKey]);
    }

    protected function resolveTitleElement(string|array $title): Element
    {
        if (is_array($title)) {
            $key = $title[0];
            $params = is_array($title[1] ?? null) ? $title[1] : [];
            $default = $title[2] ?? '';
            return Element::make('span')->intl($key, $params, $default);
        }
        return Element::make('span')->text($title);
    }

    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);
        $wrapper->class('ux-data-table-wrapper');

        if ($this->fragmentName) {
            $wrapper->liveFragment($this->fragmentName);
        }

        $hasToolbar = $this->title || $this->header || $this->searchable || !empty($this->actions) || !empty($this->batchActions);

        if ($hasToolbar) {
            $headerEl = Element::make('div')->class('ux-data-table-header');

            $leftSection = Element::make('div')->class('ux-data-table-header-left');
            $rightSection = Element::make('div')->class('ux-data-table-header-right');
            $hasLeft = false;
            $hasRight = false;

            if ($this->title) {
                $leftSection->child(Element::make('div')->class('ux-data-table-title')->text($this->title));
                $hasLeft = true;
            }

            if ($this->searchable) {
                $searchEl = $this->buildSearchElement();
                $rightSection->child($searchEl);
                $hasRight = true;
            }

            if (!empty($this->actions)) {
                $actionsEl = $this->buildActionsElement();
                $rightSection->child($actionsEl);
                $hasRight = true;
            }

            if (!empty($this->batchActions)) {
                $batchEl = $this->buildBatchActionsElement();
                $leftSection->child($batchEl);
                $hasLeft = true;
            }

            if ($this->header) {
                $rightSection->child(Element::make('div')->class('ux-data-table-header-extra')->child($this->resolveChild($this->header)));
                $hasRight = true;
            }

            if ($hasLeft) {
                $headerEl->child($leftSection);
            }
            if ($hasRight) {
                $headerEl->child($rightSection);
            }

            $wrapper->child($headerEl);
        }

        $tableEl = Element::make('table')->class('ux-data-table');
        $tableEl->class("ux-data-table-{$this->size}");
        $tableEl->data('multi-select', $this->selectable ? 'true' : 'false');

        if ($this->striped) {
            $tableEl->class('ux-data-table-striped');
        }
        if ($this->bordered) {
            $tableEl->class('ux-data-table-bordered');
        }
        if ($this->hoverable) {
            $tableEl->class('ux-data-table-hover');
        }
        if ($this->selectable) {
            $tableEl->class('ux-data-table-selectable');
        }

        $tableEl->child($this->buildColgroup());
        $tableEl->child($this->buildThead());
        $tableEl->child($this->buildTbody());

        if ($this->footer) {
            $tableEl->child($this->buildTfoot());
        }

        $wrapper->child($tableEl);

        if ($this->pagination) {
            $this->applyPaginationLive();
            $wrapper->child($this->pagination);
        }

        return $wrapper;
    }

    /**
     * 构建 colgroup 元素
     * @return Element
     * @ux-internal
     */
    protected function buildColgroup(): Element
    {
        $colgroup = Element::make('colgroup');

        if ($this->selectable) {
            $colgroup->child(Element::make('col')->class('ux-data-table-col-selection'));
        }

        foreach ($this->columns as $col) {
            if (isset($col['visible']) && !$col['visible']) {
                continue;
            }
            $colEl = Element::make('col');
            if (!empty($col['width'])) {
                $colEl->style('width:' . $col['width']);
            }
            if (!empty($col['fixed'])) {
                $colEl->class('ux-data-table-col-fixed-' . $col['fixed']);
            }
            $colgroup->child($colEl);
        }

        return $colgroup;
    }

    /**
     * 构建 thead 元素
     * @return Element
     * @ux-internal
     */
    protected function buildThead(): Element
    {
        $thead = Element::make('thead')->class('ux-data-table-thead');
        $tr = Element::make('tr')->class('ux-data-table-row');

        if ($this->selectable) {
            $selectAllEl = Element::make('input')
                ->attr('type', 'checkbox')
                ->class('ux-data-table-checkbox-all');

            $th = Element::make('th')
                ->class('ux-data-table-cell ux-data-table-selection')
                ->child($selectAllEl);
            $tr->child($th);
        }

        foreach ($this->columns as $col) {
            if (isset($col['visible']) && !$col['visible']) {
                continue;
            }

            $th = Element::make('th')->class('ux-data-table-cell ux-data-table-th');

            if (!empty($col['align'])) {
                $th->class('ux-data-table-align-' . $col['align']);
            }
            if (!empty($col['fixed'])) {
                $th->class('ux-data-table-fixed-' . $col['fixed']);
            }

            if (!empty($col['sortable'])) {
                $th->class('ux-data-table-sortable');
                $th->data('sort-key', $col['dataKey']);

                $isCurrentSort = $this->sortField === $col['dataKey'];
                $currentDir = strtolower(trim($this->sortDirection));

                if ($isCurrentSort) {
                    $th->class('ux-data-table-sorted');
                    $th->class('ux-data-table-sort-' . $currentDir);
                }

                $sortWrapper = Element::make('div')->class('ux-data-table-sort-wrapper');
                $sortWrapper->child(Element::make('span')->class('ux-data-table-sort-title')->child($this->resolveTitleElement($col['title'])));

                $sortIcon = Element::make('span')->class('ux-data-table-sort-icon');

                if ($isCurrentSort) {
                    if ($currentDir === 'asc') {
                        $sortIcon->child(Element::make('i')->class('bi bi-sort-up-alt'));
                    } else {
                        $sortIcon->child(Element::make('i')->class('bi bi-sort-down'));
                    }
                } else {
                    // 非当前排序字段显示淡色默认图标
                    $sortIcon->child(Element::make('i')->class('bi bi-sort-down text-gray-300'));
                }

                $sortWrapper->child($sortIcon);
                $th->child($sortWrapper);

                $sortAction = $this->sortAction ?? $this->liveAction;
                if ($sortAction) {
                    $nextDir = ($isCurrentSort && $currentDir === 'asc') ? 'desc' : 'asc';
                    $th->liveAction($sortAction, 'click');
                    $th->data('action-params', json_encode([
                        'sortField' => $col['dataKey'],
                        'sortDirection' => $nextDir,
                    ], JSON_UNESCAPED_UNICODE));
                }
            } else {
                $th->child($this->resolveTitleElement($col['title']));
            }
            $tr->child($th);
        }

        if ($this->rowActionsCallback) {
            $tr->child(Element::make('th')->class('ux-data-table-cell ux-data-table-th')->intl('actions'));
        }

        $thead->child($tr);
        return $thead;
    }

    /**
     * 构建 tbody 元素
     * @return Element
     * @ux-internal
     */
    protected function buildTbody(): Element
    {
        $tbody = Element::make('tbody')->class('ux-data-table-tbody');

        $data = $this->dataSource;
        $isEmpty = empty($data) || !is_array($data);

        if ($isEmpty) {
            $colspan = count(array_filter($this->columns, fn($c) => !isset($c['visible']) || $c['visible']));
            if ($this->selectable) {
                $colspan++;
            }
            $colspan++;

            $tr = Element::make('tr')->class('ux-data-table-row ux-data-table-empty');
            $td = Element::make('td')
                ->class('ux-data-table-cell ux-data-table-empty-cell')
                ->attr('colspan', (string)$colspan)
                ->child(
                    Element::make('div')->class('ux-data-table-empty-content')->intl('ux:datatable.empty_data', [],  $this->emptyText)
                );
            $tr->child($td);
            $tbody->child($tr);
            return $tbody;
        }

        foreach ($this->dataSource as $index => $row) {
            $tr = Element::make('tr')->class('ux-data-table-row');
            $rowKeyValue = (string)($row[$this->rowKey] ?? $index);
            $tr->data('row-key', $rowKeyValue);
            $tr->data('row-index', (string)$index);

            if ($index % 2 === 1 && $this->striped) {
                $tr->class('ux-data-table-row-odd');
            }

            foreach ($this->rowAttrs as $key => $value) {
                $tr->attr($key, $value);
            }

            if ($this->rowCallback) {
                $extraClasses = ($this->rowCallback)($row, $index);
                if (is_string($extraClasses)) {
                    $tr->class($extraClasses);
                } elseif (is_array($extraClasses)) {
                    foreach ($extraClasses as $cls) {
                        if (is_string($cls)) {
                            $tr->class($cls);
                        }
                    }
                }
            }

            if ($this->tooltipCallback) {
                $tooltip = ($this->tooltipCallback)($row, $index);
                if ($tooltip) {
                    $tr->attr('title', $tooltip);
                    $tr->class('ux-data-table-has-tooltip');
                }
            }

            if ($this->selectable) {
                $checkboxEl = Element::make('input')
                    ->attr('type', 'checkbox')
                    ->class('ux-data-table-checkbox')
                    ->data('row-key', $rowKeyValue);

                $td = Element::make('td')
                    ->class('ux-data-table-cell ux-data-table-selection')
                    ->child($checkboxEl);
                $tr->child($td);
            }

            foreach ($this->columns as $col) {
                if (isset($col['visible']) && !$col['visible']) {
                    continue;
                }

                $td = Element::make('td')->class('ux-data-table-cell ux-data-table-td');

                if (!empty($col['align'])) {
                    $td->class('ux-data-table-align-' . $col['align']);
                }
                if (!empty($col['fixed'])) {
                    $td->class('ux-data-table-fixed-' . $col['fixed']);
                }

                if (!empty($col['tooltip'])) {
                    $tooltip = is_string($col['tooltip']) ? $col['tooltip'] : (($col['tooltip'])($value, $row, $index) ?? '');
                    if ($tooltip) {
                        $td->attr('title', $tooltip);
                        $td->class('ux-data-table-has-tooltip');
                    }
                }

                $value = $row[$col['dataKey']] ?? null;

                if (isset($col['render']) && $col['render'] instanceof \Closure) {
                    $rendered = ($col['render'])($value, $row, $index);
                    if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                        $td->child($this->resolveChild($rendered));
                    } elseif (is_string($rendered)) {
                        $td->html($rendered);
                    } else {
                        $td->text((string)($value ?? ''));
                    }
                } else {
                    $td->text((string)($value ?? ''));
                }

                $tr->child($td);
            }

            // 渲染行操作列
            if ($this->rowActionsCallback) {
                $actionsTd = Element::make('td')->class('ux-data-table-cell ux-data-table-td ux-data-table-actions');
                $actionsTd->class('ux-data-table-align-center');

                $components = ($this->rowActionsCallback)($row, $rowKeyValue, $index);
                if (is_array($components)) {
                    $container = Element::make('div')->class('flex items-center gap-2');
                    foreach ($components as $component) {
                        if ($component instanceof UXComponent) {
                            $container->child($component->toElement());
                        } elseif ($component instanceof Element) {
                            $container->child($component);
                        }
                    }
                    $actionsTd->child($container);
                }

                $tr->child($actionsTd);
            }

            $tbody->child($tr);
        }

        return $tbody;
    }

    /**
     * 构建 tfoot 元素
     * @return Element
     * @ux-internal
     */
    protected function buildTfoot(): Element
    {
        $tfoot = Element::make('tfoot')->class('ux-data-table-tfoot');
        $tr = Element::make('tr')->class('ux-data-table-row');

        if ($this->selectable) {
            $tr->child(Element::make('td')->class('ux-data-table-cell'));
        }

        $visibleCols = array_filter($this->columns, fn($c) => !isset($c['visible']) || $c['visible']);
        $colspan = count($visibleCols);

        $td = Element::make('td')
            ->class('ux-data-table-cell')
            ->attr('colspan', (string)$colspan)
            ->child($this->resolveChild($this->footer));

        $tr->child($td);
        $tfoot->child($tr);
        return $tfoot;
    }

    /**
     * 构建搜索框元素
     * @return Element
     * @ux-internal
     */
    protected function buildSearchElement(): Element
    {
        $wrapper = Element::make('div')->class('ux-data-table-search');

        $input = Element::make('input')
            ->attr('type', 'text')
            ->attr('name', 'search')
            ->class('ux-data-table-search-input')
            ->intlAttr('placeholder', 'ux:datatable.search');

        if ($this->searchValue) {
            $input->attr('value', $this->searchValue);
        }

        $action = $this->searchAction ?? $this->liveAction;
        if ($action) {
            $input->liveAction($action, 'keydown.enter');
        } else {
            $input->data('action', 'search');
        }

        $btn = Element::make('button')
            ->attr('type', 'button')
            ->class('ux-data-table-search-btn')
            ->intl('ux:datatable.search');

        if ($action) {
            $btn->liveAction($action, 'click');
        } else {
            $btn->data('action', 'search');
        }

        $wrapper->child($input);
        $wrapper->child($btn);
        return $wrapper;
    }

    /**
     * 构建操作按钮组元素
     * @return Element
     * @ux-internal
     */
    protected function buildActionsElement(): Element
    {
        $wrapper = Element::make('div')->class('ux-data-table-actions');

        foreach ($this->actions as $name => $config) {
            $btn = Element::make('button')
                ->attr('type', 'button')
                ->class('ux-data-table-action-btn')
                ->text($config['label'] ?? $name);

            if (!empty($config['class'])) {
                $btn->class($config['class']);
            }

            if (!empty($config['icon'])) {
                $btn->data('icon', $config['icon']);
            }

            $action = $config['action'];
            $event = $config['event'] ?? 'click';
            $btn->liveAction($action, $event);

            if (!empty($config['params'])) {
                $btn->data('action-params', json_encode($config['params'], JSON_UNESCAPED_UNICODE));
            }

            $wrapper->child($btn);
        }

        return $wrapper;
    }

    /**
     * 构建批量操作元素
     * @return Element
     * @ux-internal
     */
    protected function buildBatchActionsElement(): Element
    {
        $wrapper = Element::make('div')->class('ux-data-table-batch-actions');
        $wrapper->data('batch-actions-container', 'true');

        $actionsContainer = Element::make('div')->class('ux-batch-actions');
        $actionsContainer->class('ux-batch-actions-inactive');
        $actionsContainer->data('selected-keys', '[]');
        $actionsContainer->data('selected-count', '0');

        $left = Element::make('div')->class('ux-batch-actions-left');

        $countSpan = Element::make('span')->class('ux-batch-actions-count')->text('');
        $left->child($countSpan);

        $emptySpan = Element::make('span')->class('ux-batch-actions-empty')->intl('ux:datatable.select_to_operate', [], $this->emptyText ?? '请选择要操作的记录');
        $left->child($emptySpan);

        $dropdown = Element::make('div')->class('ux-batch-actions-dropdown');
        $dropdown->style('display: none;');

        $triggerBtn = Element::make('button')
            ->attr('type', 'button')
            ->class('ux-batch-actions-trigger')
            ->child(Element::make('span')->intl('ux:datatable.batch_actions', [], '批量操作'));

        $triggerIcon = Element::make('i')->class('bi bi-chevron-down ux-batch-actions-trigger-arrow');
        $triggerBtn->child($triggerIcon);
        $dropdown->child($triggerBtn);

        $menu = Element::make('div')->class('ux-batch-actions-menu');

        foreach ($this->batchActions as $actionConfig) {
            $item = Element::make('button')
                ->attr('type', 'button')
                ->class('ux-batch-actions-item');

            if (!empty($actionConfig['variant'])) {
                $item->class("ux-batch-actions-item-{$actionConfig['variant']}");
            }

            if (!empty($actionConfig['icon'])) {
                $iconClass = str_starts_with($actionConfig['icon'], 'bi-') ? $actionConfig['icon'] : 'bi-' . $actionConfig['icon'];
                $item->child(Element::make('i')->class($iconClass . ' ux-batch-actions-item-icon'));
            }

            $item->child(Element::make('span')->class('ux-batch-actions-item-label')->text($actionConfig['label']));

            if ($this->batchAction) {
                $item->liveAction($this->batchAction, 'click');
                $item->data('action-params', json_encode([
                    'batchAction' => $actionConfig['action'],
                    'selectedKeys' => [],
                    'confirm' => $actionConfig['confirm'] ?? null,
                ], JSON_UNESCAPED_UNICODE));
            }

            if (!empty($actionConfig['confirm'])) {
                $item->data('confirm', $actionConfig['confirm']);
            }

            $menu->child($item);
        }

        $dropdown->child($menu);
        $left->child($dropdown);

        $actionsContainer->child($left);

        $cancelBtn = Element::make('button')
            ->attr('type', 'button')
            ->class('ux-batch-actions-cancel')
            ->child(Element::make('span')->intl('ux:datatable.cancel_selection', [], '取消选择'));
        $cancelBtn->data('ux-action', 'cancelSelection');
        $cancelBtn->data('action-event', 'click');
        $actionsContainer->child($cancelBtn);

        $wrapper->child($actionsContainer);
        return $wrapper;
    }

    /**
     * 应用分页 Live 配置
     * @return void
     * @ux-internal
     */
    protected function applyPaginationLive(): void
    {
        if (!$this->pagination) return;

        $action = $this->pageAction ?? $this->liveAction;
        if ($action) {
            $this->pagination->liveAction($action, $this->liveEvent ?? 'click');
        }

        // 确保分页组件知道每页条数选项和动作
        if (method_exists($this->pagination, 'perPageOptions')) {
            $this->pagination->perPageOptions($this->perPageOptions);
        }

        if ($this->perPageAction && method_exists($this->pagination, 'perPageAction')) {
            $this->pagination->perPageAction($this->perPageAction);
        } elseif ($action && method_exists($this->pagination, 'perPageAction')) {
            // 如果没有显式的每页条数动作，但有分页动作，默认使用它
            $this->pagination->perPageAction($action);
        }

        if ($this->showPerPage && method_exists($this->pagination, 'showPerPage')) {
            $this->pagination->showPerPage($this->total, $this->perPage, $this->page);
        }
    }
}
