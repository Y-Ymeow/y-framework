<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UI\Pagination;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

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

    public function column(string $dataKey, string $title, ?\Closure $render = null, array $options = []): static
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
     * Add a column using DataTableColumn object (chainable)
     */
    public function addColumn(DataTableColumn $column): static
    {
        $this->columns[] = $column->toArray();
        return $this;
    }

    /**
     * Get searchable columns
     */
    public function getSearchableColumns(): array
    {
        return array_filter($this->columns, fn($col) => !empty($col['searchable']));
    }

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

    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    public function rows(array $data): static
    {
        return $this->dataSource($data);
    }

    public function rowKey(string $key): static
    {
        $this->rowKey = $key;
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

    public function striped(bool $striped = true): static
    {
        $this->striped = $striped;
        return $this;
    }

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    public function hoverable(bool $hoverable = true): static
    {
        $this->hoverable = $hoverable;
        return $this;
    }

    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;
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
        $this->pagination->class("px-6! py-4!");
        $this->pagination->total($total)->current($current)->perPage($perPage);
        if ($baseUrl) {
            $this->pagination->baseUrl($baseUrl);
        }
        return $this;
    }

    public function paginationComponent(Pagination $pagination): static
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function rowAttr(string $key, string $value): static
    {
        $this->rowAttrs[$key] = $value;
        return $this;
    }

    public function rowCallback(\Closure $callback): static
    {
        $this->rowCallback = $callback;
        return $this;
    }

    public function sortField(?string $field): static
    {
        $this->sortField = $field;
        return $this;
    }

    public function sortDirection(string $direction): static
    {
        $this->sortDirection = $direction;
        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    public function sortAction(string $action): static
    {
        $this->sortAction = $action;
        return $this;
    }

    public function pageAction(string $action): static
    {
        $this->pageAction = $action;
        return $this;
    }

    public function selectAction(string $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    public function registerAction(string $name, string $action, string $event = 'click', array $config = []): static
    {
        $this->actions[$name] = array_merge([
            'action' => $action,
            'event' => $event,
        ], $config);
        return $this;
    }

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

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function searchAction(string $action): static
    {
        $this->searchAction = $action;
        return $this;
    }

    public function searchValue(?string $value): static
    {
        $this->searchValue = $value;
        return $this;
    }

    public function searchPlaceholder(string $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;
        return $this;
    }

    public function batchActions(array $actions): static
    {
        $this->batchActions = $actions;
        return $this;
    }

    public function batchAction(string $action): static
    {
        $this->batchAction = $action;
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

    public function showPerPage(bool $show = true, int $total = 0, int $perPage = 15, int $page = 1): static
    {
        $this->showPerPage = $show;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->page = $page;
        return $this;
    }

    public function tooltip(?\Closure $callback): static
    {
        $this->tooltipCallback = $callback;
        return $this;
    }

    /**
     * 注册行操作组件
     * 闭包接收 ($row, $rowKey, $rowIndex) 返回组件数组
     */
    public function rowActions(\Closure $callback): static
    {
        $this->rowActionsCallback = $callback;
        return $this;
    }

    /**
     * 启用行内编辑
     *
     * @param array $columns 可编辑的列 ['column_key' => ['type' => 'input', 'rules' => []], ...]
     * @param string $action 保存编辑的动作
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
     *
     * @param string $type input|textarea|select|switch
     */
    public function editType(string $type): static
    {
        $this->editType = $type;
        return $this;
    }

    /**
     * 获取可编辑的列
     */
    public function getEditableColumns(): array
    {
        return $this->editableColumns;
    }

    /**
     * 检查列是否可编辑
     */
    public function isColumnEditable(string $columnKey): bool
    {
        return isset($this->editableColumns[$columnKey]);
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
            $wrapper->child($this->pagination->toElement());
        }

        return $wrapper;
    }

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
                $sortWrapper->child(Element::make('span')->class('ux-data-table-sort-title')->text($col['title']));

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
                $th->text($col['title']);
            }
            $tr->child($th);
        }

        if ($this->rowActionsCallback) {
            $tr->child(Element::make('th')->class('ux-data-table-cell ux-data-table-th')->text(t('actions')));
        }

        $thead->child($tr);
        return $thead;
    }

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
            $emptyText = $this->emptyText ?? t('ux.empty_data');
            $tr = Element::make('tr')->class('ux-data-table-row ux-data-table-empty');
            $td = Element::make('td')
                ->class('ux-data-table-cell ux-data-table-empty-cell')
                ->attr('colspan', (string)$colspan)
                ->child(
                    Element::make('div')->class('ux-data-table-empty-content')->text($emptyText)
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

    protected function buildSearchElement(): Element
    {
        $wrapper = Element::make('div')->class('ux-data-table-search');

        $input = Element::make('input')
            ->attr('type', 'text')
            ->attr('name', 'search')
            ->class('ux-data-table-search-input')
            ->attr('placeholder', $this->searchPlaceholder ?? t('ux.search') . '...');

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
            ->text(t('ux.search'));

        if ($action) {
            $btn->liveAction($action, 'click');
        } else {
            $btn->data('action', 'search');
        }

        $wrapper->child($input);
        $wrapper->child($btn);
        return $wrapper;
    }

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

        $emptySpan = Element::make('span')->class('ux-batch-actions-empty')->text($this->emptyText ?? '请选择要操作的记录');
        $left->child($emptySpan);

        $dropdown = Element::make('div')->class('ux-batch-actions-dropdown');
        $dropdown->style('display: none;');

        $triggerBtn = Element::make('button')
            ->attr('type', 'button')
            ->class('ux-batch-actions-trigger')
            ->text('批量操作');

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
            ->text('取消选择');
        $cancelBtn->data('ux-action', 'cancelSelection');
        $cancelBtn->data('action-event', 'click');
        $actionsContainer->child($cancelBtn);

        $wrapper->child($actionsContainer);
        return $wrapper;
    }

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
