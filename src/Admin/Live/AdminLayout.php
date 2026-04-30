<?php

declare(strict_types=1);

namespace Framework\Admin\Live;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\Session;
use Framework\Admin\AdminManager;
use Framework\UX\Dialog\Drawer;
use Framework\UX\Menu\Dropdown;
use Framework\UX\Menu\Menu;
use Framework\UX\UI\Avatar;
use Framework\UX\UI\Badge;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;

class AdminLayout extends LiveComponent
{
    public string $activeMenu = '';

    #[Session]
    public bool $sidebarCollapsed = false;

    #[Session]
    public array $expandedGroups = [];

    protected mixed $content = null;

    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function mount(): void
    {
        $request = app()->make(\Framework\Http\Request::class);
        $path = $request->path();
        $prefix = AdminManager::getPrefix();

        // 尝试从 URI 提取资源名称或页面名称
        if (str_starts_with($path, $prefix)) {
            $subPath = trim(substr($path, strlen($prefix)), '/');
            $parts = explode('/', $subPath);
            $this->activeMenu = $parts[0] ?: 'dashboard';
        }
    }

    public function render(): string|Element
    {
        $el = Element::make('div')->class('admin-layout', 'flex', 'h-screen', 'bg-gray-50');

        // 注入菜单激活样式
        $style = Element::make('style')->text("
            .ux-menu-item-active .ux-menu-link {
                background-color: #f3f4f6 !important; /* bg-gray-100 */
                color: #111827 !important;            /* text-gray-900 */
                font-weight: 500 !important;          /* font-medium */
            }
            .ux-menu-item-active .ux-menu-icon {
                color: #4b5563 !important;            /* text-gray-600 */
            }
        ");
        $el->child($style);

        $el->child($this->renderSidebar());
        $el->child($this->renderMain());
        $el->child($this->renderNotificationDrawer());

        return $el;
    }

    #[LiveAction]
    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
        $this->refresh('admin-sidebar');
    }

    #[LiveAction]
    public function toggleGroup(?string $id = null, bool $open = false): void
    {
        if ($id === null) return;

        if ($open) {
            if (!in_array($id, $this->expandedGroups, true)) {
                $this->expandedGroups[] = $id;
            }
        } else {
            $this->expandedGroups = array_values(array_diff($this->expandedGroups, [$id]));
        }
        $this->refresh('admin-sidebar');
    }

    #[LiveAction]
    public function navigate(string $menu): void
    {
        $this->activeMenu = $menu;
        $this->refresh('admin-sidebar');
        $this->refresh('admin-content');
    }

    protected function renderSidebar(): Element
    {
        $sidebar = Element::make('aside')
            ->class('admin-sidebar', 'flex', 'flex-col', 'h-full', 'bg-white', 'border-r', 'border-gray-200', 'transition-all', 'duration-300')
            ->id('admin-sidebar');
        $sidebar->liveFragment('admin-sidebar');

        if ($this->sidebarCollapsed) {
            $sidebar->class('admin-sidebar-collapsed', 'w-16');
        } else {
            $sidebar->class('w-64');
        }

        $sidebar->attr('data-effect', "sidebarCollapsed ? \$el.classList.add('admin-sidebar-collapsed') : \$el.classList.remove('admin-sidebar-collapsed'); localStorage.setItem('admin_sidebar_collapsed', sidebarCollapsed ? '1' : '0')");

        // Brand
        $brand = Element::make('div')
            ->class('admin-sidebar-brand', 'flex', 'items-center', 'h-14', 'px-4', 'border-b', 'border-gray-200', 'shrink-0');
        $brand->child(Element::make('div')
            ->class('admin-sidebar-brand-text', 'font-semibold', 'text-gray-900', 'truncate')
            ->text(AdminManager::getBrandTitle()));
        $sidebar->child($brand);

        // Navigation
        $nav = Element::make('nav')
            ->class('admin-sidebar-nav', 'flex-1', 'overflow-y-auto', 'overflow-x-hidden', 'py-2');

        $groups = $this->getMenuGroups();

        $menu = Menu::make()->vertical()->class('admin-sidebar-menu');

        if (!$this->sidebarCollapsed) {
        }

        foreach ($groups as $groupName => $items) {
            if ($groupName === '' || $this->sidebarCollapsed) {
                // 无分组 或 sidebar 折叠时平铺
                foreach ($items as $item) {
                    $menu->item(
                        $item['title'],
                        $item['prefix'] . '/' . $item['name'],
                        'circle',
                        $this->activeMenu === $item['name']
                    );
                }
            } else {
                // 有分组
                $isOpen = in_array($groupName, $this->expandedGroups, true);
                $menu->group($groupName, null, $isOpen, $groupName);
                foreach ($items as $item) {
                    $menu->subitem(
                        $item['title'],
                        $item['prefix'] . '/' . $item['name'],
                        'circle',
                        $this->activeMenu === $item['name']
                    );
                }
            }
        }

        $nav->child($menu);

        $sidebar->child($nav);
        return $sidebar;
    }

    protected function getMenuGroups(): array
    {
        $groups = [];
        $prefix = AdminManager::getPrefix();

        // 默认总是添加仪表盘
        $groups[''][] = [
            'name' => 'dashboard',
            'title' => '控制台',
            'prefix' => $prefix,
        ];

        $resources = AdminManager::getResources();
        foreach ($resources as $resourceClass) {
            $group = '';
            $ref = new \ReflectionClass($resourceClass);
            $attrs = $ref->getAttributes(\Framework\Admin\Attribute\AdminResource::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                $group = (string)$attr->group;
            }

            $groups[$group][] = [
                'name' => $resourceClass::getName(),
                'title' => $resourceClass::getTitle(),
                'prefix' => $prefix,
            ];
        }

        $pages = AdminManager::getPages();
        foreach ($pages as $pageClass) {
            if ($pageClass::getName() === 'dashboard') continue;

            $groups[''][] = [
                'name' => $pageClass::getName(),
                'title' => $pageClass::getTitle(),
                'prefix' => $prefix,
            ];
        }

        return $groups;
    }

    protected function renderMain(): Element
    {
        $main = Element::make('div')
            ->class('admin-main', 'flex', 'flex-col', 'flex-1', 'min-w-0', 'h-full', 'overflow-hidden');

        $main->child($this->renderHeader());

        $content = Element::make('div')
            ->class('admin-content', 'flex-1', 'overflow-y-auto', 'p-6')
            ->id('admin-content')
            ->attr('data-navigate-fragment', 'admin-content');
        $content->liveFragment('admin-content');

        if ($this->content) {
            if ($this->content instanceof \Framework\Component\Live\LiveComponent) {
                $content->child($this->content->toHtml());
            } else {
                $content->child($this->content);
            }
        }

        $main->child($content);

        $main->child($this->renderFooter());

        return $main;
    }

    protected function renderHeader()
    {
        $header = Element::make('header')
            ->class('admin-header', 'flex', 'items-center', 'justify-between', 'h-14', 'px-4', 'bg-white', 'border-b', 'border-gray-200', 'shrink-0');

        // Left
        $left = Element::make('div')->class('flex', 'items-center', 'gap-3');

        $toggleBtn = Button::make()
            ->bi($this->sidebarCollapsed ? 'chevron-bar-right' : 'chevron-bar-left')
            ->variant('ghost')
            ->liveAction('toggleSidebar');
        $left->child($toggleBtn);

        $header->child($left);

        // Center: Search
        $center = Element::make('div')->class('flex-1', 'max-w-md', 'mx-4');

        $searchWrapper = Element::make('div')->class('ux-search');
        $searchWrapper->data('search', 'true');

        $searchInput = Element::make('input')
            ->attr('type', 'search')
            ->class('ux-form-input', 'ux-search-input')
            ->attr('placeholder', '全局搜索...')
            ->attr('autocomplete', 'off');
        $searchWrapper->child($searchInput);

        $searchWrapper->child(Element::make('i')->class('bi', 'bi-search', 'ux-search-icon'));
        $searchWrapper->child(Element::make('div')->class('ux-search-results'));

        $center->child($searchWrapper);
        $header->child($center);

        // Right
        $right = Element::make('div')->class('flex', 'items-center', 'gap-2');

        // Notification button (triggers drawer)
        $notifBtn = Button::make()
            ->bi('bell')
            ->variant('ghost')
            ->class('relative')
            ->attr('data-ux-drawer-toggle', 'notification-drawer');

        $notifBadge = Badge::make()
            ->dot()
            ->danger()
            ->class('absolute', 'top-1.5', 'right-1.5');
        $notifBtn->child($notifBadge);
        $right->child($notifBtn);

        // User dropdown
        $userDropdown = $this->renderUserDropdown();
        $right->child($userDropdown);

        $header->child($right);

        return $header;
    }

    protected function renderUserDropdown()
    {
        $trigger = Element::make('button')
            ->class('flex', 'items-center', 'gap-2', 'pl-2', 'border-l', 'border-gray-200')
            ->attr('type', 'button');

        $trigger->child(Avatar::make()->name('管理员')->size('sm'));
        $trigger->child(Element::make('span')->class('text-sm', 'text-gray-700', 'hidden', 'md:block')->text('管理员'));
        $trigger->child(Element::make('i')->class('bi', 'bi-chevron-down', 'text-xs', 'text-gray-400'));

        return Dropdown::make()
            ->noborder()
            ->position('bottom-end')
            ->customTrigger($trigger)
            ->item('设置', '#', 'gear')
            ->divider()
            ->element(
                Element::make('a')
                    ->class('ux-dropdown-link', 'flex', 'items-center', 'gap-2', 'text-red-600', 'px-4', 'py-2')
                    ->attr('href', '#')
                    ->child(Element::make('i')->class('bi', 'bi-box-arrow-right'))
                    ->child(Element::make('span')->text('登出')->class('text-sm'))
            );
    }

    protected function renderNotificationDrawer()
    {
        return Drawer::make()
            ->id('notification-drawer')
            ->title('通知')
            ->right()
            ->md()
            ->child(Element::make('div')->class('p-4', 'text-gray-500')->text('暂无通知'));
    }

    protected function renderFooter(): Element
    {
        $footer = Element::make('footer')
            ->class('admin-footer', 'shrink-0', 'py-3', 'px-6', 'bg-white', 'border-t', 'border-gray-200');

        $inner = Element::make('div')
            ->class('flex', 'items-center', 'justify-between', 'text-sm', 'text-gray-500');

        $inner->child(Element::make('span')->text('© 2024 Admin Dashboard'));
        $inner->child(Element::make('span')->class('text-xs')->text('Powered by Framework'));

        $footer->child($inner);
        return $footer;
    }
}
