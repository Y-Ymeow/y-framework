<?php

declare(strict_types=1);

namespace Framework\Auth;

use Framework\Database\Connection;
use Framework\Http\Session;
use Framework\Http\Cookie;

class AuthManager
{
    private Session $session;
    private Connection $connection;
    private ?User $user = null;
    private string $userModel = User::class;

    public function __construct(Session $session, Connection $connection)
    {
        $this->session = $session;
        $this->connection = $connection;
    }

    public function setUserModel(string $model): self
    {
        $this->userModel = $model;
        return $this;
    }

    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->retrieveByCredentials($credentials);

        if (!$user) {
            return false;
        }

        if (!$this->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $remember);
        return true;
    }

    public function login(User $user, bool $remember = false): void
    {
        $this->session->set('auth_user', $user->toArray());
        $this->session->set('auth_id', $user->getKey());
        $this->session->regenerate();

        if ($remember) {
            $this->setRememberToken($user);
        }

        $this->user = $user;
    }

    public function logout(): void
    {
        if ($this->user) {
            $this->user->clearToken();
        }

        $this->session->remove('auth_user');
        $this->session->remove('auth_id');
        Cookie::forget('remember_token');
        $this->session->regenerate();
        $this->user = null;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->id();
        if ($id) {
            $this->user = $this->userModel::find($id);
            return $this->user;
        }

        $user = $this->getUserByRememberToken();
        if ($user) {
            $this->user = $user;
            return $this->user;
        }

        return null;
    }

    public function id(): mixed
    {
        return $this->session->get('auth_id');
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) return false;
        $roles = $user->roles ?? [];
        return in_array($role, (array)$roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->user();
        if (!$user) return false;
        $permissions = $user->permissions ?? [];
        return in_array($permission, (array)$permissions, true);
    }

    protected function retrieveByCredentials(array $credentials): ?User
    {
        if (isset($credentials['email'])) {
            return $this->userModel::findByEmail($credentials['email']);
        }

        return null;
    }

    protected function validateCredentials(User $user, array $credentials): bool
    {
        if (isset($credentials['password'])) {
            return $user->verifyPassword($credentials['password']);
        }

        return false;
    }

    protected function setRememberToken(User $user): void
    {
        $token = $user->generateToken();
        Cookie::forever('remember_token', $token);
    }

    protected function getUserByRememberToken(): ?User
    {
        $token = $_COOKIE['remember_token'] ?? null;
        
        if (!$token) {
            return null;
        }

        $user = $this->userModel::findByToken($token);

        if ($user) {
            $this->session->set('auth_user', $user->toArray());
            $this->session->set('auth_id', $user->getKey());
        }

        return $user;
    }

    public function once(array $credentials): bool
    {
        $user = $this->retrieveByCredentials($credentials);

        if (!$user || !$this->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->user = $user;
        return true;
    }

    public function loginUsingId(mixed $id): ?User
    {
        $user = $this->userModel::find($id);

        if (!$user) {
            return null;
        }

        $this->login($user);
        return $user;
    }
}
