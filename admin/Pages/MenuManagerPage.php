<?php

declare(strict_types=1);

namespace Admin\Pages;

use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Admin\Content\Menu;
use Admin\Content\MenuItem;
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\View\Base\Element;

class MenuManagerPage extends LiveComponent implements PageInterface
{
    #[State]
    public int $selectedMenuId = 0;

    #[State]
    public string $newItemTitle = '';

    #[State]
    public string $newItemUrl = '';

    #[State]
    public string $newItemIcon = '';

    #[State]
    public string $newItemTarget = '_self';

    #[State]
    public string $newItemPermission = '';

    #[State]
    public int $editingItemId = 0;

    #[State]
    public string $editTitle = '';

    #[State]
    public string $editUrl = '';

    #[State]
    public string $editIcon = '';

    #[State]
    public string $editTarget = '_self';

    #[State]
    public string $editPermission = '';

    #[State]
    public string $newMenuName = '';

    #[State]
    public string $newMenuSlug = '';

    #[State]
    public bool $showNewMenuForm = false;

    public function mount(): void
    {
        $menus = Menu::all();
        if (!empty($menus) && $this->selectedMenuId === 0) {
            $first = is_array($menus[0]) ? $menus[0] : $menus[0]->toArray();
            $this->selectedMenuId = (int)($first['id'] ?? 0);
        }
    }

    public static function getName(): string
    {
        return 'menus';
    }

    public static function getTitle(): string|array
    {
        return ['admin:menus.title', [], '菜单管理'];
    }

    public static function getIcon(): string
    {
        return 'list';
    }

    public static function getGroup(): string
    {
        return 'admin.system';
    }

    public static function getSort(): int
    {
        return 53;
    }

    public static function getRoutes(): array
    {
        return [
            'admin.menus' => [
                'method' => 'GET',
                'path' => '/menus',
                'handler' => function () {
                    return static::renderPage();
                },
            ],
        ];
    }

    public static function renderPage()
    {
        $page = new static();
        $page->named('admin-page-menus');

        $layout = new AdminLayout();
        $layout->activeMenu = 'menus';
        $layout->setContent($page);

        return $layout;
    }

    #[LiveAction]
    public function selectMenu(array $params): void
    {
        $this->selectedMenuId = (int)($params['menuId'] ?? 0);
        $this->editingItemId = 0;
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function addMenuItem(array $params): void
    {
        $title = trim($params['title'] ?? $this->newItemTitle);
        $url = trim($params['url'] ?? $this->newItemUrl);

        if (empty($title) || $this->selectedMenuId === 0) {
            $this->toast('请填写标题并选择菜单', 'error');
            return;
        }

        $maxSort = db()->table('menu_items')
            ->where('menu_id', $this->selectedMenuId)
            ->max('sort') ?? 0;

        MenuItem::create([
            'menu_id' => $this->selectedMenuId,
            'parent_id' => null,
            'title' => $title,
            'url' => $url,
            'icon' => trim($params['icon'] ?? $this->newItemIcon),
            'target' => trim($params['target'] ?? $this->newItemTarget) ?: '_self',
            'permission' => trim($params['permission'] ?? $this->newItemPermission),
            'sort' => $maxSort + 1,
            'is_active' => true,
        ]);

        $this->newItemTitle = '';
        $this->newItemUrl = '';
        $this->newItemIcon = '';
        $this->newItemTarget = '_self';
        $this->newItemPermission = '';

        $this->toast('菜单项已添加');
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function removeItem(array $params): void
    {
        $itemId = (int)($params['itemId'] ?? 0);
        if ($itemId === 0) return;

        db()->table('menu_items')->where('parent_id', $itemId)->update(['parent_id' => null]);
        MenuItem::destroy($itemId);

        $this->toast('菜单项已删除');
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function saveOrder(array $params): void
    {
        $orderData = $params['order'] ?? [];
        if (empty($orderData)) return;

        foreach ($orderData as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id === 0) continue;

            db()->table('menu_items')->where('id', $id)->update([
                'parent_id' => isset($item['parentId']) && $item['parentId'] !== '' ? (int)$item['parentId'] : null,
                'sort' => (int)($item['sort'] ?? 0),
            ]);
        }

        $this->toast('排序已保存');
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function editItem(array $params): void
    {
        $itemId = (int)($params['itemId'] ?? 0);
        if ($itemId === 0) return;

        $item = MenuItem::find($itemId);
        if (!$item) return;

        $this->editingItemId = $itemId;
        $data = $item->toArray();
        $this->editTitle = $data['title'] ?? '';
        $this->editUrl = $data['url'] ?? '';
        $this->editIcon = $data['icon'] ?? '';
        $this->editTarget = $data['target'] ?? '_self';
        $this->editPermission = $data['permission'] ?? '';

        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function saveItem(array $params): void
    {
        $itemId = (int)($params['itemId'] ?? $this->editingItemId);
        if ($itemId === 0) return;

        $item = MenuItem::find($itemId);
        if (!$item) return;

        $item->title = trim($params['title'] ?? $this->editTitle);
        $item->url = trim($params['url'] ?? $this->editUrl);
        $item->icon = trim($params['icon'] ?? $this->editIcon);
        $item->target = trim($params['target'] ?? $this->editTarget) ?: '_self';
        $item->permission = trim($params['permission'] ?? $this->editPermission);
        $item->save();

        $this->editingItemId = 0;
        $this->toast('菜单项已更新');
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        $this->editingItemId = 0;
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function toggleActive(array $params): void
    {
        $itemId = (int)($params['itemId'] ?? 0);
        if ($itemId === 0) return;

        $item = MenuItem::find($itemId);
        if (!$item) return;

        $item->is_active = !$item->is_active;
        $item->save();
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function createMenu(array $params): void
    {
        $name = trim($params['name'] ?? $this->newMenuName);
        $slug = trim($params['slug'] ?? $this->newMenuSlug);

        if (empty($name) || empty($slug)) {
            $this->toast('请填写菜单名称和标识', 'error');
            return;
        }

        $menu = Menu::create(['name' => $name, 'slug' => $slug]);
        $this->selectedMenuId = (int)($menu->id ?? 0);
        $this->newMenuName = '';
        $this->newMenuSlug = '';
        $this->showNewMenuForm = false;

        $this->toast('菜单已创建');
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function deleteMenu(array $params): void
    {
        $menuId = (int)($params['menuId'] ?? 0);
        if ($menuId === 0) return;

        db()->table('menu_items')->where('menu_id', $menuId)->delete();
        Menu::destroy($menuId);

        if ($this->selectedMenuId === $menuId) {
            $menus = Menu::all();
            if (!empty($menus)) {
                $first = is_array($menus[0]) ? $menus[0] : $menus[0]->toArray();
                $this->selectedMenuId = (int)($first['id'] ?? 0);
            } else {
                $this->selectedMenuId = 0;
            }
        }

        $this->toast('菜单已删除');
        $this->refresh('menu-editor');
    }

    #[LiveAction]
    public function toggleNewMenuForm(): void
    {
        $this->showNewMenuForm = !$this->showNewMenuForm;
        $this->refresh('menu-editor');
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')->class('menu-manager');

        $header = Element::make('div')->class('menu-manager-header');
        $header->child(Element::make('h1')->class('menu-manager-title')->intl('admin:menus.title', [], '菜单管理'));
        $wrapper->child($header);

        $body = Element::make('div')->class('menu-manager-body');
        $body->child($this->renderSidebar());
        $body->child($this->renderEditor());
        $wrapper->child($body);

        return $wrapper;
    }

    protected function renderSidebar(): Element
    {
        $sidebar = Element::make('div')
            ->class('menu-manager-sidebar')
            ->liveFragment('menu-sidebar');

        $sidebar->child(
            Element::make('div')->class('menu-sidebar-header')->children(
                Element::make('h2')->class('menu-sidebar-title')->text('菜单位置'),
                Element::make('button')
                    ->class('menu-sidebar-add-btn')
                    ->attr('data-action:click', 'toggleNewMenuForm()')
                    ->html('<i class="bi bi-plus-lg"></i>')
            )
        );

        if ($this->showNewMenuForm) {
            $form = Element::make('div')->class('menu-new-form');
            $form->child(
                Element::make('input')
                    ->class('menu-input')
                    ->attr('type', 'text')
                    ->attr('placeholder', '菜单名称')
                    ->attr('data-live-model', 'newMenuName')
            );
            $form->child(
                Element::make('input')
                    ->class('menu-input')
                    ->attr('type', 'text')
                    ->attr('placeholder', '标识 (如: main_nav)')
                    ->attr('data-live-model', 'newMenuSlug')
            );
            $form->child(
                Element::make('div')->class('menu-new-form-actions')->children(
                    Element::make('button')
                        ->class('menu-btn', 'menu-btn-primary', 'menu-btn-sm')
                        ->attr('data-action:click', 'createMenu()')
                        ->text('创建'),
                    Element::make('button')
                        ->class('menu-btn', 'menu-btn-ghost', 'menu-btn-sm')
                        ->attr('data-action:click', 'toggleNewMenuForm()')
                        ->text('取消')
                )
            );
            $sidebar->child($form);
        }

        $menus = Menu::all();
        $menuList = Element::make('div')->class('menu-sidebar-list');

        foreach ($menus as $menuData) {
            $m = is_array($menuData) ? $menuData : $menuData->toArray();
            $id = (int)($m['id'] ?? 0);
            $name = $m['name'] ?? '';
            $slug = $m['slug'] ?? '';
            $isActive = $id === $this->selectedMenuId;

            $item = Element::make('div')->class('menu-sidebar-item', $isActive ? 'active' : '');
            $item->attr('data-action:click', 'selectMenu()')
                ->attr('data-action-params', json_encode(['menuId' => $id], JSON_UNESCAPED_UNICODE));

            $itemInfo = Element::make('div')->class('menu-sidebar-item-info');
            $itemInfo->child(Element::make('div')->class('menu-sidebar-item-name')->text($name));
            $itemInfo->child(Element::make('div')->class('menu-sidebar-item-slug')->text($slug));
            $item->child($itemInfo);

            $delBtn = Element::make('button')
                ->class('menu-sidebar-item-delete')
                ->attr('data-action:click', 'deleteMenu()')
                ->attr('data-action-params', json_encode(['menuId' => $id], JSON_UNESCAPED_UNICODE))
                ->html('<i class="bi bi-trash3"></i>');
            $item->child($delBtn);

            $menuList->child($item);
        }

        $sidebar->child($menuList);

        return $sidebar;
    }

    protected function renderEditor(): Element
    {
        $editor = Element::make('div')
            ->class('menu-manager-editor')
            ->liveFragment('menu-editor');

        if ($this->selectedMenuId === 0) {
            $editor->child(
                Element::make('div')->class('menu-editor-empty')->text('请选择一个菜单位置')
            );
            return $editor;
        }

        $editor->child($this->renderAddForm());
        $editor->child($this->renderTree());

        return $editor;
    }

    protected function renderAddForm(): Element
    {
        $form = Element::make('div')->class('menu-add-form');
        $form->child(Element::make('h3')->class('menu-add-form-title')->text('添加链接'));

        $fields = Element::make('div')->class('menu-add-form-fields');

        $fields->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('标题 *'),
                Element::make('input')
                    ->class('menu-input')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'newItemTitle')
                    ->attr('placeholder', '菜单项标题')
            )
        );

        $fields->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('链接'),
                Element::make('input')
                    ->class('menu-input')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'newItemUrl')
                    ->attr('placeholder', '/path 或 https://...')
            )
        );

        $row = Element::make('div')->class('menu-form-row');
        $row->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('图标'),
                Element::make('input')
                    ->class('menu-input')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'newItemIcon')
                    ->attr('placeholder', 'bi-icon-name')
            )
        );
        $row->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('打开方式'),
                Element::make('select')
                    ->class('menu-select')
                    ->attr('data-live-model', 'newItemTarget')
                    ->html('<option value="_self">当前窗口</option><option value="_blank">新窗口</option>')
            )
        );
        $row->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('权限'),
                Element::make('input')
                    ->class('menu-input')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'newItemPermission')
                    ->attr('placeholder', 'permission.key')
            )
        );
        $fields->child($row);

        $form->child($fields);
        $form->child(
            Element::make('div')->class('menu-add-form-actions')->child(
                Element::make('button')
                    ->class('menu-btn', 'menu-btn-primary')
                    ->attr('data-action:click', 'addMenuItem()')
                    ->text('添加到菜单')
            )
        );

        return $form;
    }

    protected function renderTree(): Element
    {
        $menu = Menu::find($this->selectedMenuId);
        if (!$menu) {
            return Element::make('div')->class('menu-tree-empty')->text('菜单不存在');
        }

        $tree = $menu->getItemsTree();

        $container = Element::make('div')->class('menu-tree');
        $container->child(Element::make('h3')->class('menu-tree-title')->text('菜单结构'));

        if (empty($tree)) {
            $container->child(Element::make('div')->class('menu-tree-empty')->text('暂无菜单项，请添加'));
            return $container;
        }

        $treeList = Element::make('div')
            ->class('menu-tree-list')
            ->attr('data-menu-sortable', '')
            ->attr('data-menu-id', (string)$this->selectedMenuId);

        $this->renderTreeItems($treeList, $tree, null);

        $container->child($treeList);
        $container->child(
            Element::make('button')
                ->class('menu-btn', 'menu-btn-outline', 'menu-btn-sm', 'menu-save-order-btn')
                ->attr('onclick', 'window.MenuManager && window.MenuManager.saveCurrentOrder(this)')
                ->text('保存排序')
        );

        return $container;
    }

    protected function renderTreeItems(Element $parent, array $items, ?int $parentId): void
    {
        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            $title = $item['title'] ?? '';
            $url = $item['url'] ?? '';
            $icon = $item['icon'] ?? '';
            $isActive = $item['is_active'] ?? true;
            $children = $item['children'] ?? [];

            $isEditing = $id === $this->editingItemId;

            $node = Element::make('div')
                ->class('menu-tree-item', !$isActive ? 'disabled' : '')
                ->attr('data-item-id', (string)$id)
                ->attr('data-parent-id', $parentId !== null ? (string)$parentId : '');

            $handle = Element::make('div')->class('menu-tree-item-handle')->html('<i class="bi bi-grip-vertical"></i>');
            $node->child($handle);

            if ($isEditing) {
                $node->class('editing');
                $node->child($this->renderEditForm($item));
            } else {
                $content = Element::make('div')->class('menu-tree-item-content');

                $label = Element::make('div')->class('menu-tree-item-label');
                if ($icon) {
                    $label->child(Element::make('i')->class('bi', 'bi-' . $icon, 'menu-tree-item-icon'));
                }
                $label->child(Element::make('span')->class('menu-tree-item-title')->text($title));
                if ($url) {
                    $label->child(Element::make('span')->class('menu-tree-item-url')->text($url));
                }
                $content->child($label);

                $actions = Element::make('div')->class('menu-tree-item-actions');
                $actions->child(
                    Element::make('button')
                        ->class('menu-tree-item-action')
                        ->attr('data-action:click', 'editItem()')
                        ->attr('data-action-params', json_encode(['itemId' => $id], JSON_UNESCAPED_UNICODE))
                        ->html('<i class="bi bi-pencil"></i>')
                );
                $actions->child(
                    Element::make('button')
                        ->class('menu-tree-item-action')
                        ->attr('data-action:click', 'toggleActive()')
                        ->attr('data-action-params', json_encode(['itemId' => $id], JSON_UNESCAPED_UNICODE))
                        ->html($isActive ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>')
                );
                $actions->child(
                    Element::make('button')
                        ->class('menu-tree-item-action', 'menu-tree-item-action-danger')
                        ->attr('data-action:click', 'removeItem()')
                        ->attr('data-action-params', json_encode(['itemId' => $id], JSON_UNESCAPED_UNICODE))
                        ->html('<i class="bi bi-trash3"></i>')
                );
                $content->child($actions);

                $node->child($content);
            }

            $childList = Element::make('div')
                ->class('menu-tree-item-children')
                ->attr('data-menu-sortable-children', '');

            if (!empty($children)) {
                $this->renderTreeItems($childList, $children, $id);
            }

            $parent->child($node);
            $parent->child($childList);
        }
    }

    protected function renderEditForm(array $item): Element
    {
        $id = (int)($item['id'] ?? 0);

        $form = Element::make('div')->class('menu-tree-item-edit');

        $form->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('标题'),
                Element::make('input')
                    ->class('menu-input', 'menu-input-sm')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'editTitle')
                    ->attr('value', $this->editTitle)
            )
        );

        $form->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('链接'),
                Element::make('input')
                    ->class('menu-input', 'menu-input-sm')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'editUrl')
                    ->attr('value', $this->editUrl)
            )
        );

        $row = Element::make('div')->class('menu-form-row');
        $row->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('图标'),
                Element::make('input')
                    ->class('menu-input', 'menu-input-sm')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'editIcon')
                    ->attr('value', $this->editIcon)
            )
        );
        $row->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('打开方式'),
                Element::make('select')
                    ->class('menu-select', 'menu-select-sm')
                    ->attr('data-live-model', 'editTarget')
                    ->html(
                        '<option value="_self"' . ($this->editTarget === '_self' ? ' selected' : '') . '>当前窗口</option>' .
                        '<option value="_blank"' . ($this->editTarget === '_blank' ? ' selected' : '') . '>新窗口</option>'
                    )
            )
        );
        $row->child(
            Element::make('div')->class('menu-form-group')->children(
                Element::make('label')->class('menu-form-label')->text('权限'),
                Element::make('input')
                    ->class('menu-input', 'menu-input-sm')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'editPermission')
                    ->attr('value', $this->editPermission)
            )
        );
        $form->child($row);

        $form->child(
            Element::make('div')->class('menu-tree-item-edit-actions')->children(
                Element::make('button')
                    ->class('menu-btn', 'menu-btn-primary', 'menu-btn-sm')
                    ->attr('data-action:click', 'saveItem()')
                    ->attr('data-action-params', json_encode(['itemId' => $id], JSON_UNESCAPED_UNICODE))
                    ->text('保存'),
                Element::make('button')
                    ->class('menu-btn', 'menu-btn-ghost', 'menu-btn-sm')
                    ->attr('data-action:click', 'cancelEdit()')
                    ->text('取消')
            )
        );

        return $form;
    }

}
