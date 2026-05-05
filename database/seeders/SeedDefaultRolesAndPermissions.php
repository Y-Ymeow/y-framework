<?php

declare(strict_types=1);

namespace Database\Seeders;

use Admin\Auth\Role;
use Admin\Auth\Permission;

class SeedDefaultRolesAndPermissions
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->assignPermissions();
    }

    protected function seedPermissions(): void
    {
        $modules = [
            'user' => ['view_any', 'view', 'create', 'update', 'delete'],
            'role' => ['view_any', 'view', 'create', 'update', 'delete'],
            'menu' => ['view_any', 'view', 'create', 'update', 'delete'],
            'media' => ['view_any', 'upload', 'update', 'delete'],
            'post' => ['view_any', 'view', 'create', 'update', 'delete', 'publish'],
            'category' => ['view_any', 'view', 'create', 'update', 'delete'],
            'setting' => ['view', 'update'],
            'theme' => ['view', 'update'],
            'schedule' => ['view', 'update'],
            'page' => ['view', 'create', 'update', 'delete'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                $exists = db()->table('permissions')->where('slug', $slug)->first();
                if (!$exists) {
                    db()->table('permissions')->insert([
                        'name' => "{$module}.{$action}",
                        'slug' => $slug,
                        'module' => $module,
                        'description' => '',
                    ]);
                }
            }
        }
    }

    protected function seedRoles(): void
    {
        $roles = [
            ['name' => '超级管理员', 'slug' => 'super_admin', 'description' => '拥有所有权限', 'is_system' => 1],
            ['name' => '管理员', 'slug' => 'admin', 'description' => '大部分管理权限', 'is_system' => 1],
            ['name' => '编辑', 'slug' => 'editor', 'description' => '内容管理权限', 'is_system' => 1],
            ['name' => '查看者', 'slug' => 'viewer', 'description' => '只读权限', 'is_system' => 1],
        ];

        foreach ($roles as $role) {
            $exists = db()->table('roles')->where('slug', $role['slug'])->first();
            if (!$exists) {
                db()->table('roles')->insert($role);
            }
        }
    }

    protected function assignPermissions(): void
    {
        $allPermissions = db()->table('permissions')->get()->toArray();

        $superAdmin = db()->table('roles')->where('slug', 'super_admin')->first();
        if ($superAdmin) {
            foreach ($allPermissions as $p) {
                $this->attachPermission((int)$superAdmin['id'], (int)$p['id']);
            }
        }

        $admin = db()->table('roles')->where('slug', 'admin')->first();
        if ($admin) {
            $adminModules = ['user', 'role', 'menu', 'media', 'post', 'category', 'setting', 'page'];
            $adminExclude = ['role.delete'];
            foreach ($allPermissions as $p) {
                if (in_array($p['module'], $adminModules) && !in_array($p['slug'], $adminExclude)) {
                    $this->attachPermission((int)$admin['id'], (int)$p['id']);
                }
            }
        }

        $editor = db()->table('roles')->where('slug', 'editor')->first();
        if ($editor) {
            $editorModules = ['post', 'category', 'media'];
            foreach ($allPermissions as $p) {
                if (in_array($p['module'], $editorModules)) {
                    $this->attachPermission((int)$editor['id'], (int)$p['id']);
                }
            }
        }

        $viewer = db()->table('roles')->where('slug', 'viewer')->first();
        if ($viewer) {
            foreach ($allPermissions as $p) {
                if (str_ends_with($p['slug'], '.view_any') || str_ends_with($p['slug'], '.view')) {
                    $this->attachPermission((int)$viewer['id'], (int)$p['id']);
                }
            }
        }
    }

    protected function attachPermission(int $roleId, int $permissionId): void
    {
        try {
            db()->table('role_permissions')
                ->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        } catch (\Throwable $e) {
            // already exists, skip
        }
    }
}
