<?php

declare(strict_types=1);

namespace Database\Seeders;

class SeedDefaultMenus
{
    public function run(): void
    {
        $menuId = $this->createMenu('admin_sidebar', '管理后台侧边栏');

        $items = [
            ['title' => '控制台', 'url' => '/admin', 'icon' => 'speedometer2', 'permission' => null, 'sort' => 0],
            ['title' => '内容管理', 'url' => null, 'icon' => 'file-earmark-text', 'permission' => null, 'sort' => 10, 'children' => [
                ['title' => '文章管理', 'url' => '/admin/posts', 'icon' => 'circle', 'permission' => 'post.view_any', 'sort' => 11],
                ['title' => '分类管理', 'url' => '/admin/categories', 'icon' => 'circle', 'permission' => 'category.view_any', 'sort' => 12],
                ['title' => '标签管理', 'url' => '/admin/tags', 'icon' => 'circle', 'permission' => 'category.view_any', 'sort' => 13],
            ]],
            ['title' => '媒体库', 'url' => '/admin/media', 'icon' => 'folder2-open', 'permission' => 'media.view_any', 'sort' => 20],
            ['title' => '页面管理', 'url' => '/admin/pages', 'icon' => 'file-earmark', 'permission' => 'page.view', 'sort' => 30],
            ['title' => '外观', 'url' => null, 'icon' => 'palette', 'permission' => null, 'sort' => 40, 'children' => [
                ['title' => '主题管理', 'url' => '/admin/themes', 'icon' => 'circle', 'permission' => 'theme.view', 'sort' => 41],
            ]],
            ['title' => '系统管理', 'url' => null, 'icon' => 'gear', 'permission' => null, 'sort' => 50, 'children' => [
                ['title' => '用户管理', 'url' => '/admin/users', 'icon' => 'circle', 'permission' => 'user.view_any', 'sort' => 51],
                ['title' => '角色管理', 'url' => '/admin/roles', 'icon' => 'circle', 'permission' => 'role.view_any', 'sort' => 52],
                ['title' => '菜单管理', 'url' => '/admin/menus', 'icon' => 'circle', 'permission' => 'menu.view_any', 'sort' => 53],
                ['title' => '计划任务', 'url' => '/admin/schedule', 'icon' => 'circle', 'permission' => 'schedule.view', 'sort' => 54],
                ['title' => '系统设置', 'url' => '/admin/settings', 'icon' => 'circle', 'permission' => 'setting.view', 'sort' => 55],
            ]],
            ['title' => '日志', 'url' => null, 'icon' => 'journal-text', 'permission' => null, 'sort' => 60, 'children' => [
                ['title' => '操作日志', 'url' => '/admin/activity-log', 'icon' => 'circle', 'permission' => 'user.view_any', 'sort' => 61],
                ['title' => '登录日志', 'url' => '/admin/login-log', 'icon' => 'circle', 'permission' => 'user.view_any', 'sort' => 62],
            ]],
        ];

        foreach ($items as $item) {
            $this->createMenuItem($menuId, null, $item);
        }
    }

    protected function createMenu(string $slug, string $name): int
    {
        $exists = db()->table('menus')->where('slug', $slug)->first();
        if ($exists) {
            return (int)$exists['id'];
        }

        db()->table('menus')->insert([
            'name' => $name,
            'slug' => $slug,
        ]);

        return (int)db()->table('menus')->where('slug', $slug)->first()['id'];
    }

    protected function createMenuItem(int $menuId, ?int $parentId, array $data): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        db()->table('menu_items')->insert(array_merge($data, [
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            'is_active' => true,
        ]));

        if (!empty($children)) {
            $parentItem = db()->table('menu_items')
                ->where('menu_id', $menuId)
                ->where('title', $data['title'])
                ->where('sort', $data['sort'])
                ->first();

            if ($parentItem) {
                foreach ($children as $child) {
                    $this->createMenuItem($menuId, (int)$parentItem['id'], $child);
                }
            }
        }
    }
}
