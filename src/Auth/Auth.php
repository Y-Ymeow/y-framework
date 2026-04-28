<?php

declare(strict_types=1);

namespace Framework\Auth;

use Framework\Http\Session;

class Auth
{
    private Session $session;
    private static ?Auth $instance = null;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public static function make(Session $session): self
    {
        if (self::$instance === null) {
            self::$instance = new self($session);
        }
        return self::$instance;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function login(array $user, bool $remember = false): void
    {
        $this->session->set('auth_user', $user);
        $this->session->set('auth_id', $user['id'] ?? null);
        $this->session->regenerate();

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            \Framework\Http\Cookie::forever('remember_token', $token);
        }
    }

    public function logout(): void
    {
        $this->session->remove('auth_user');
        $this->session->remove('auth_id');
        \Framework\Http\Cookie::forget('remember_token');
        $this->session->regenerate();
    }

    public function check(): bool
    {
        return $this->session->has('auth_id');
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?array
    {
        return $this->session->get('auth_user');
    }

    public function id(): mixed
    {
        return $this->session->get('auth_id');
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) return false;
        $roles = $user['roles'] ?? [];
        return in_array($role, (array)$roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->user();
        if (!$user) return false;
        $permissions = $user['permissions'] ?? [];
        return in_array($permission, (array)$permissions, true);
    }
}
