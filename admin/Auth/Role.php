<?php

declare(strict_types=1);

namespace Admin\Auth;

use Framework\Database\Model;

class Role extends Model
{
    protected string $table = 'roles';
    protected array $fillable = ['name', 'slug', 'description', 'is_system'];
    protected array $casts = [
        'is_system' => 'bool',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->slug === 'super_admin') {
            return true;
        }

        $perms = $this->getPermissionSlugs();
        return in_array($permission, $perms, true);
    }

    public function getPermissionSlugs(): array
    {
        $results = db()->table('role_permissions')
            ->select('permissions.slug')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $this->id)
            ->get();

        return array_map(fn($r) => $r['slug'], $results);
    }

    public function syncPermissions(array $permissionIds): void
    {
        db()->table('role_permissions')->where('role_id', $this->id)->delete();

        foreach ($permissionIds as $pid) {
            db()->table('role_permissions')->insert([
                'role_id' => $this->id,
                'permission_id' => (int)$pid,
            ]);
        }
    }

    public function getPermissionCount(): int
    {
        return db()->table('role_permissions')
            ->where('role_id', $this->id)
            ->count();
    }

    public function getUserCount(): int
    {
        return db()->table('user_roles')
            ->where('role_id', $this->id)
            ->count();
    }
}
