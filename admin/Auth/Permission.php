<?php

declare(strict_types=1);

namespace Admin\Auth;

use Framework\Database\Model;

class Permission extends Model
{
    protected string $table = 'permissions';
    protected array $fillable = ['name', 'slug', 'module', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    public static function getByModule(): array
    {
        $all = static::all();
        $grouped = [];
        foreach ($all as $perm) {
            $module = $perm['module'] ?? 'other';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }
        return $grouped;
    }
}
