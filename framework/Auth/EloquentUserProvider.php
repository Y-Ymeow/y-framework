<?php

declare(strict_types=1);

namespace Framework\Auth;

/**
 * EloquentUserProvider - 基于 Eloquent Model 的用户数据提供者
 *
 * 作为 AuthManager 和 User Model 之间的桥梁，
 * 负责"如何从数据库中查找/验证用户"。
 * 不依赖具体 Model 实现，只依赖 Authenticatable 接口。
 */
class EloquentUserProvider implements UserProvider
{
    private string $model;

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function retrieveById(mixed $id): ?Authenticatable
    {
        return $this->model::find($id);
    }

    public function retrieveByToken(string $identifier, string $token): ?Authenticatable
    {
        $instance = $this->makeInstance();

        $result = $this->model::where(
            $instance->getAuthIdentifierName(),
            $identifier
        )->first();

        if (!$result) {
            return null;
        }

        $rememberToken = $result->getRememberToken();
        if ($rememberToken && hash_equals($rememberToken, $token)) {
            return $result;
        }

        return null;
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials)) {
            return null;
        }

        $query = $this->model::query();

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }
            if (is_array($value) || $value instanceof \Closure) {
                continue;
            }
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return password_verify($credentials['password'], $user->getAuthPassword());
    }

    public function updateRememberToken(Authenticatable $user, string $token): void
    {
        $user->setRememberToken($token);
        if ($user instanceof \Framework\Database\Model && $user->exists) {
            $user->save();
        }
    }

    private function makeInstance(): Authenticatable
    {
        return new $this->model();
    }
}
