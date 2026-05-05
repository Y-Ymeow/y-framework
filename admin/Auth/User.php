<?php

declare(strict_types=1);

namespace Admin\Auth;

use Framework\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];
    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function getRoleSlugs(): array
    {
        $results = db()->table('user_roles')
            ->select('roles.slug')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $this->id)
            ->get();

        return array_map(fn($r) => $r['slug'], $results);
    }

    public function getRoleNames(): array
    {
        $results = db()->table('user_roles')
            ->select('roles.name')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $this->id)
            ->get();

        return array_map(fn($r) => $r['name'], $results);
    }

    public function hasRole(string|array $roles): bool
    {
        $userRoles = $this->getRoleSlugs();

        if (in_array('super_admin', $userRoles, true)) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        $userRoles = $this->getRoleSlugs();

        if (in_array('super_admin', $userRoles, true)) {
            return true;
        }

        $roleIds = db()->table('user_roles')
            ->where('user_id', $this->id)
            ->get();
        $roleIds = array_map(fn($r) => $r['role_id'], $roleIds);

        if (empty($roleIds)) {
            return false;
        }

        $permExists = db()->table('role_permissions')
            ->select('permissions.slug')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->where('permissions.slug', $permission)
            ->first();

        return !empty($permExists);
    }

    public function syncRoles(array $roleIds): void
    {
        db()->table('user_roles')->where('user_id', $this->id)->delete();

        foreach ($roleIds as $rid) {
            db()->table('user_roles')->insert([
                'user_id' => $this->id,
                'role_id' => (int)$rid,
            ]);
        }
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = $this->hashPassword($value);
    }

    public static function findByEmail(string $email): ?self
    {
        $result = static::where('email', $email)->first();
        if (!$result) {
            return null;
        }
        return static::find($result['id']);
    }

    public static function findByToken(string $token): ?self
    {
        $result = static::where('remember_token', $token)->first();
        if (!$result) {
            return null;
        }
        return static::find($result['id']);
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->remember_token = $token;
        $this->save();
        return $token;
    }

    public function clearToken(): void
    {
        $this->remember_token = null;
        $this->save();
    }
}
