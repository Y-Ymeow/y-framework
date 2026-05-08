<?php

declare(strict_types=1);

namespace Admin\Contracts\Live;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Admin\Contracts\Resource\ResourceInterface;
use Admin\Contracts\Resource\BaseResource;
use Admin\Services\AdminManager;
use Framework\UX\Data\DataTable;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;
use Framework\UX\UXComponent;

class AdminListPage extends LiveComponent
{
    #[State(frontendEditable: false)]
    public string $resourceName = '';

    #[State]
    public int $page = 1;

    #[State]
    public int $perPage = 15;

    #[State]
    public string $sortField = '';

    #[State]
    public string $sortDirection = 'asc';

    #[State]
    public string $searchQuery = '';

    #[State]
    public array $selectedKeys = [];

    public function mount(): void
    {
        if (empty($this->resourceName)) return;
        $this->sortField = $this->getDefaultSortField();
        $this->registerResourceActions();
    }

    protected function registerResourceActions(): void
    {
        $resource = $this->getResource();
        if (!$resource) return;

        // 从 Resource 获取手动注册的 LiveActions
        $resourceActions = $resource->getLiveActions();
        foreach ($resourceActions as $name => $config) {
            $this->registerAction($name, $config);
        }
    }

    #[LiveAction]
    public function search(array $params): void
    {
        $this->searchQuery = $params['value'] ?? $params['searchQuery'] ?? '';
        $this->page = 1;
        $this->refresh('admin-list-table');
    }

    #[LiveAction]
    public function sort(array $params): void
    {
        $newField = $params['sortField'] ?? $this->sortField;

        // 同一列点击 → 切换方向；不同列 → 默认 asc
        if ($newField === $this->sortField) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $newField;
        $this->refresh('admin-list-table');
    }

    #[LiveAction]
    public function loadPage(int $page = 1, int $perPage = 15): void
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->refresh('admin-list-table');
    }

    #[LiveAction]
    public function loadPerPage(int $perPage = 15): void
    {
        $this->perPage = $perPage > 0 ? $perPage : 15;
        $this->page = 1;
        $this->refresh('admin-list-table');
    }

    #[LiveAction]
    public function deleteSelected(array $params): void
    {
        $keys = $params['selectedKeys'] ?? [];
        if (empty($keys)) {
            $keys = $this->selectedKeys;
        }

        if (empty($keys)) {
            $this->toast(t('admin:actions.select_first', [], '请先选择要删除的项目'), 'error');
            return;
        }

        $resource = $this->getResource();
        if (!$resource) return;

        $modelClass = $resource::getModel();
        foreach ($keys as $key) {
            $modelClass::destroy($key);
        }
        $this->selectedKeys = [];
        $this->toast(t('admin:actions.delete_success', [], '删除成功'));
        $this->refresh('admin-list-table');
    }

    #[LiveAction]
    public function editRow(int $rowKey): void
    {
        if (!$rowKey) return;

        $resourceName = $this->getResource()->getName();
        if (!$resourceName) return;

        $prefix = AdminManager::getPrefix() ?: '/admin';
        $url = "{$prefix}/{$resourceName}/{$rowKey}/edit";
        $this->navigateTo($url);
    }

    #[LiveAction]
    public function deleteRow(int $rowKey): void
    {
        if (!$rowKey) return;

        $resourceName = $this->getResource()->getName();
        if (!$resourceName) return;

        $resource = AdminManager::getResource($resourceName);
        if (!$resource) return;

        $modelClass = $resource::getModel();
        $modelClass::destroy($rowKey);

        parent::toast(t('admin:actions.delete_success', [], '删除成功'));
        $this->refresh('admin-list-table');
    }

    public function render(): Element
    {
        $resource = $this->getResource();
        if (!$resource) {
            return Element::make('div')->class('admin-list')->text('Resource not found');
        }

        $wrapper = Element::make('div')->class('admin-list');

        $title = $resource::getTitle();
        $params = [];
        $defaultText = '';

        if (is_array($title)) {
            $params = $title[1] ?? [];
            $defaultText = $title[2] ?? '';
            $title = $title[0];
        }

        $headerEl = Element::make('div')->class('admin-list-header');
        $headerEl->child(Element::make('h1')->class('admin-list-title')->intl($title, $params, $defaultText));

        $prefix = AdminManager::getPrefix() ?: '/admin';
        $name = $resource::getName();
        $createLink = Element::make('a')
            ->class('admin-btn admin-btn-primary admin-btn-sm')
            ->attr('href', "{$prefix}/{$name}/create")
            ->attr('data-navigate', '')
            ->child(Element::make('span')->intl('admin:actions.create', [], '新增'));
        $headerEl->child(Element::make('div')->class('admin-list-actions')->child($createLink));
        $wrapper->child($headerEl);

        if ($resource instanceof BaseResource) {
            $this->renderListLifecycle($wrapper, $resource);
        } else {
            $headerContent = $resource->getHeader();
            if ($headerContent !== null) {
                $wrapper->child($this->resolveContent($headerContent));
            }

            $tableHtml = $this->buildTable($resource)->render();
            $wrapper->child($tableHtml);

            $footerContent = $resource->getFooter();
            if ($footerContent !== null) {
                $wrapper->child($this->resolveContent($footerContent));
            }
        }

        return $wrapper;
    }

    protected function renderListLifecycle(Element $wrapper, BaseResource $resource): void
    {
        $beforeHeader = $resource->getListBeforeHeader();
        if ($beforeHeader !== null) {
            $wrapper->child($this->resolveContent($beforeHeader));
        }

        $resourceHeader = $resource->getListHeader() ?? $resource->getHeader();
        if ($resourceHeader !== null) {
            $wrapper->child($this->resolveContent($resourceHeader));
        }

        $afterHeader = $resource->getListAfterHeader();
        if ($afterHeader !== null) {
            $wrapper->child($this->resolveContent($afterHeader));
        }

        $beforeTable = $resource->getListBeforeTable();
        if ($beforeTable !== null) {
            $wrapper->child($this->resolveContent($beforeTable));
        }

        $tableHtml = $this->buildTable($resource)->render();
        $wrapper->child($tableHtml);

        $afterTable = $resource->getListAfterTable();
        if ($afterTable !== null) {
            $wrapper->child($this->resolveContent($afterTable));
        }

        $beforeFooter = $resource->getListBeforeFooter();
        if ($beforeFooter !== null) {
            $wrapper->child($this->resolveContent($beforeFooter));
        }

        $resourceFooter = $resource->getListFooter() ?? $resource->getFooter();
        if ($resourceFooter !== null) {
            $wrapper->child($this->resolveContent($resourceFooter));
        }

        $afterFooter = $resource->getListAfterFooter();
        if ($afterFooter !== null) {
            $wrapper->child($this->resolveContent($afterFooter));
        }
    }

    /**
     * 将 Resource getHeader/getFooter 的返回值统一解析为 Element
     */
    protected function resolveContent(mixed $content): mixed
    {
        if ($content instanceof Element || $content instanceof UXComponent || $content instanceof LiveComponent) {
            return $content;
        }
        return Element::make('div')->text((string)$content);
    }

    protected function buildTable(ResourceInterface $resource): DataTable
    {
        $table = new DataTable();
        $resource->configureTable($table);

        $modelClass = $resource::getModel();
        $query = $modelClass::query();

        if ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // 从 DataTable 的 searchable columns 自动生成 SQL WHERE 条件
        $searchableColumns = $table->getSearchableColumns();
        if ($this->searchQuery && !empty($searchableColumns)) {
            $query->where(function ($q) use ($searchableColumns) {
                foreach ($searchableColumns as $index => $col) {
                    $field = $col['dataKey'];
                    $type = $col['searchType'] ?? 'like';
                    $operator = match ($type) {
                        '=' => '=',
                        'in' => 'IN',
                        default => 'LIKE',
                    };
                    $value = match ($type) {
                        'like' => "%{$this->searchQuery}%",
                        'in' => explode(',', $this->searchQuery),
                        default => $this->searchQuery,
                    };
                    if ($index === 0) {
                        $q->where($field, $operator, $value);
                    } else {
                        $q->orWhere($field, $operator, $value);
                    }
                }
            });
        }

        $total = $query->count();
        $offset = ($this->page - 1) * $this->perPage;
        $rows = $query->offset($offset)->limit($this->perPage)->get();

        if ($rows instanceof \Framework\Support\Collection) {
            $rows = array_filter($rows->all(), fn($r) => !empty($r));
        } elseif (is_array($rows)) {
            $rows = array_filter($rows, fn($r) => !empty($r));
        } else {
            $rows = [];
        }

        $table->dataSource($rows);
        $table->selectable();
        $table->striped();
        $table->hoverable();
        $table->sortField($this->sortField);
        $table->sortDirection($this->sortDirection);
        $table->fragment('admin-list-table');
        $table->sortAction('sort');
        $table->pageAction('loadPage');
        $table->perPageAction('loadPerPage');
        $table->selectAction('selectRow');
        $table->searchAction('search');
        $table->searchable();
        $table->batchAction('deleteSelected');
        $table->batchActions([
            [
                'label' => t('admin:actions.batch_delete', [], '批量删除'),
                'action' => 'delete',
                'variant' => 'danger',
                'icon' => 'trash',
                'confirm' => t('admin:actions.confirm_delete_selected', [], '确定要删除选中的记录吗？')
            ]
        ]);
        // 行操作通过 $table->rowActions() 在 Resource 中直接注册组件

        $table->pagination($total, $this->page, $this->perPage);
        // 默认展开搜索和每页条数
        $table->showPerPage(true, $total, $this->perPage, $this->page);

        return $table;
    }

    protected function getResource(): ?ResourceInterface
    {
        $resourceClass = AdminManager::getResource($this->resourceName);
        if (!$resourceClass) return null;
        return new $resourceClass();
    }

    protected function getDefaultSortField(): string
    {
        return 'id';
    }

    public static function resource(string $resourceName): \Closure
    {
        return function () use ($resourceName) {
            $page = new static();
            $page->resourceName = $resourceName;
            $page->named("admin-list-{$resourceName}");

            $layout = new AdminLayout();
            $layout->activeMenu = $resourceName;
            $layout->setContent($page);

            return $layout;
        };
    }
}
