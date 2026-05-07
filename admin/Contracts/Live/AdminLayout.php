<?php

declare(strict_types=1);

namespace Admin\Contracts\Live;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\Session;
use Admin\Services\AdminManager;
use Framework\UX\Dialog\Drawer;
use Framework\UX\Dialog\Toast;
use Framework\UX\Menu\Dropdown;
use Framework\UX\Menu\Menu;
use Framework\UX\Display\Avatar;
use Framework\UX\Display\Badge;
use Framework\UX\UI\Button;
use Framework\UX\Navigation\LanguageSwitcher;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\View\Document\Document;

class AdminLayout extends LiveComponent
{
    protected bool $loading = true;
    public string $activeMenu = '';

    #[Session]
    public bool $sidebarCollapsed = false;

    #[Session]
    public array $expandedGroups = [];

    protected mixed $content = null;

    private static bool $cssRegistered = false;

    public function mount(): void
    {
        if (!self::$cssRegistered) {
            self::$cssRegistered = true;
            AssetRegistry::getInstance()->addCssSnippet('admin:layout', $this->getAdminCss());
        }

        $registry = AssetRegistry::getInstance();
        if (!$registry->isLoaded('bootstrap-icons')) {
            $registry->css('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', 'bootstrap-icons');
        }

        $request = app()->make(\Framework\Http\Request\Request::class);
        $path = $request->path();
        $prefix = AdminManager::getPrefix() ?: '/admin';

        if (str_starts_with($path, $prefix)) {
            $subPath = trim(substr($path, strlen($prefix)), '/');
            $parts = explode('/', $subPath);
            $this->activeMenu = $parts[0] ?: 'dashboard';
        }

        $title = $this->resolvePageTitle();
        Document::setTitle($title . ' | ' . t('admin.admin_panel'));
    }

    private function resolvePageTitle(): string
    {
        $menu = $this->activeMenu;
        $titles = [
            'dashboard' => t('admin.dashboard'),
            'settings' => t('admin.settings.title'),
            'users' => t('admin.user_management'),
        ];
        return $titles[$menu] ?? ucfirst($menu);
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function render(): Element
    {
        $el = Element::make('div')->class('admin-layout', 'flex', 'h-screen', 'bg-gray-50');

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
                foreach ($items as $item) {
                    $url = $item['url'];
                    $icon = $item['icon'] ?? 'circle';
                    $menu->item(
                        $item['title'],
                        $url,
                        $icon,
                        $this->activeMenu === $item['name']
                    );
                }
            } else {
                $hasActive = false;
                foreach ($items as $item) {
                    if ($this->activeMenu === $item['name']) {
                        $hasActive = true;
                        break;
                    }
                }
                $isOpen = $hasActive || in_array($groupName, $this->expandedGroups, true);

                $groupLabel = $this->resolveGroupLabel($groupName);
                $menu->group($groupLabel, null, $isOpen, $groupName);
                foreach ($items as $item) {
                    $url = $item['url'];
                    $icon = $item['icon'] ?? 'circle';

                    $menu->subitem(
                        $item['title'],
                        $url,
                        $icon,
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
        $prefix = AdminManager::getPrefix() ?: '/admin';

        $groups[''][] = [
            'name' => 'dashboard',
            'title' => ['admin.dashboard', [], '控制台'],
            'url' => $prefix,
            'icon' => 'speedometer2',
            'sort' => 0,
        ];

        $resources = AdminManager::getResources();
        foreach ($resources as $resourceClass) {
            $group = '';
            $icon = 'circle';
            $sort = 50;

            $ref = new \ReflectionClass($resourceClass);
            $attrs = $ref->getAttributes(\Admin\Contracts\Resource\AdminResource::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                $group = (string)$attr->group;
                $icon = (string)$attr->icon ?: 'circle';
                $sort = (int)$attr->sort;
            }

            $name = $resourceClass::getName();
            $groups[$group][] = [
                'name' => $name,
                'title' => $resourceClass::getTitle(),
                'url' => $prefix . '/' . $name,
                'icon' => $icon,
                'sort' => $sort,
            ];
        }

        $pages = AdminManager::getPages();
        foreach ($pages as $pageClass) {
            $name = $pageClass::getName();
            if (in_array($name, ['dashboard', 'login'], true)) continue;

            $group = '';
            $icon = 'circle';
            $sort = 50;

            if (method_exists($pageClass, 'getGroup')) {
                $group = (string)$pageClass::getGroup();
            }
            if (method_exists($pageClass, 'getIcon')) {
                $icon = (string)$pageClass::getIcon() ?: 'circle';
            }
            if (method_exists($pageClass, 'getSort')) {
                $sort = (int)$pageClass::getSort();
            }

            $groups[$group][] = [
                'name' => $name,
                'title' => $pageClass::getTitle(),
                'url' => $prefix . '/' . $name,
                'icon' => $icon,
                'sort' => $sort,
            ];
        }

        foreach ($groups as $groupName => &$items) {
            usort($items, fn($a, $b) => ($a['sort'] ?? 50) <=> ($b['sort'] ?? 50));
        }
        unset($items);

        $sorted = [];
        $sorted[''] = $groups[''] ?? [];
        unset($groups['']);
        foreach ($groups as $groupName => $items) {
            $sorted[$groupName] = $items;
        }

        return array_filter($sorted, fn($items) => !empty($items));
    }

    protected function resolveGroupLabel(string $groupName): string|array
    {
        $groupMap = [
            'admin.system' => ['admin.groups.system', [], '系统管理'],
            'admin.content' => ['admin.groups.content', [], '内容管理'],
        ];

        return $groupMap[$groupName] ?? $groupName;
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
            ->intlAttr('placeholder', 'admin.global_search', [], '全局搜索...')
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

        $langSwitcher = LanguageSwitcher::make()->sm();
        $right->child($langSwitcher);

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

        $trigger->child(Avatar::make()->name(t('admin.administrator'))->size('sm'));
        $trigger->child(Element::make('span')->class('text-sm', 'text-gray-700', 'hidden', 'md:block')->intl('admin.administrator'));
        $trigger->child(Element::make('i')->class('bi', 'bi-chevron-down', 'text-xs', 'text-gray-400'));

        return Dropdown::make()
            ->noborder()
            ->position('bottom-end')
            ->customTrigger($trigger)
            ->item(t('admin.settings'), '#', 'gear')
            ->divider()
            ->element(
                Element::make('a')
                    ->class('ux-dropdown-link', 'flex', 'items-center', 'gap-2', 'text-red-600', 'px-4', 'py-2')
                    ->attr('href', (AdminManager::getPrefix() ?: '/admin') . '/logout')
                    ->child(Element::make('i')->class('bi', 'bi-box-arrow-right'))
                    ->child(Element::make('span')->intl('admin.logout')->class('text-sm'))
            );
    }

    protected function renderNotificationDrawer()
    {
        return Drawer::make()
            ->id('notification-drawer')
            ->title(t('admin.notifications'))
            ->right()
            ->md()
            ->child(Element::make('div')->class('p-4', 'text-gray-500')->intl('admin.no_notifications', [], '暂无通知'));
    }

    protected function renderFooter(): Element
    {
        $footer = Element::make('footer')
            ->class('admin-footer', 'shrink-0', 'py-3', 'px-6', 'bg-white', 'border-t', 'border-gray-200');

        $inner = Element::make('div')
            ->class('flex', 'items-center', 'justify-between', 'text-sm', 'text-gray-500');

        $inner->child(Element::make('span')->intl('admin.footer_copyright', [], '© 2024 Admin Dashboard'));
        $inner->child(Element::make('span')->class('text-xs')->intl('admin.footer_powered', [], 'Powered by Framework'));

        $footer->child($inner);
        $footer->child(Toast::make());
        return $footer;
    }

    protected function getAdminCss(): string
    {
        return <<<'CSS'
.admin-layout {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
.admin-sidebar {
    box-shadow: inset -1px 0 0 rgba(0,0,0,0.05);
}
.admin-sidebar-collapsed .admin-sidebar-brand-text,
.admin-sidebar-collapsed .ux-menu-label,
.admin-sidebar-collapsed .ux-menu-arrow,
.admin-sidebar-collapsed .ux-menu-group-header span:not(:first-child) {
    display: none;
}
.admin-sidebar-collapsed .ux-menu-link {
    justify-content: center;
    padding: 0.5rem;
}
.admin-sidebar-collapsed .ux-menu-group-header {
    justify-content: center;
    padding: 0.5rem;
}
.admin-sidebar-collapsed .ux-menu-icon {
    margin: 0;
}
.admin-header {
    box-shadow: inset 0 -1px 0 rgba(0,0,0,0.05);
}
.admin-list {
    max-width: 100%;
}
.admin-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}
.admin-list-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.admin-list-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.admin-list-stats {
    margin-bottom: 1rem;
    color: #6b7280;
    font-size: 0.875rem;
}
.admin-form-wrapper {
    max-width: 48rem;
}
.admin-form-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}
.admin-form-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.admin-form-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f3f4f6;
}
.admin-form-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}
.admin-form-errors {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.admin-form-error-item {
    font-size: 0.875rem;
    padding: 0.125rem 0;
}
.admin-form-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    color: #075985;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}
.admin-login {
    display: flex;
    align-items: center;
    justify-content: center;
}
.admin-settings {
    padding: 0;
}
.admin-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    white-space: nowrap;
    border: 1px solid transparent;
}
.admin-btn-primary {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}
.admin-btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}
.admin-btn-secondary {
    background: #fff;
    color: #374151;
    border-color: #d1d5db;
}
.admin-btn-secondary:hover {
    background: #f9fafb;
}
.admin-btn-danger {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
}
.admin-btn-danger:hover {
    background: #dc2626;
}
.admin-btn-sm {
    padding: 0.25rem 0.625rem;
    font-size: 0.8125rem;
}
CSS;
    }
}
