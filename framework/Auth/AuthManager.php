<?php

declare(strict_types=1);

namespace Framework\Auth;

use Framework\Http\Session\Session;
use Framework\Http\Cookie\CookieJar;

class AuthManager
{
    private Session $session;
    private ?UserProvider $provider = null;
    private string $providerName = 'eloquent';
    private ?Authenticatable $user = null;
    private array $customCreators = [];

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function extend(string $driver, callable $callback): self
    {
        $this->customCreators[$driver] = $callback;
        return $this;
    }

    public function provider(?string $name = null): UserProvider
    {
        $name ??= $this->providerName;

        if ($this->provider === null) {
            $this->provider = $this->resolveProvider($name);
        }

        return $this->provider;
    }

    public function setProvider(UserProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function guard(): self
    {
        return $this;
    }

    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->provider()->retrieveByCredentials($credentials);

        if (!$user) {
            return false;
        }

        if (!$this->provider()->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $remember);
        return true;
    }

    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->session->set('auth_id', $user->getAuthIdentifier());
        $this->session->set('auth_type', get_class($user));
        $this->session->regenerate();

        if ($remember) {
            $token = hash('sha256', bin2hex(random_bytes(32)));
            $this->provider()->updateRememberToken($user, $token);
            CookieJar::foreverCookie('remember_token', $token);
            CookieJar::foreverCookie('remember_id', (string)$user->getAuthIdentifier());
        }

        $this->user = $user;
    }

    public function loginUsingId(mixed $id, bool $remember = false): ?Authenticatable
    {
        $user = $this->provider()->retrieveById($id);

        if (!$user) {
            return null;
        }

        $this->login($user, $remember);
        return $user;
    }

    public function once(array $credentials): bool
    {
        $user = $this->provider()->retrieveByCredentials($credentials);

        if (!$user || !$this->provider()->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->user = $user;
        return true;
    }

    public function onceUsingId(mixed $id): ?Authenticatable
    {
        $user = $this->provider()->retrieveById($id);

        if ($user) {
            $this->user = $user;
        }

        return $user;
    }

    public function logout(): void
    {
        $user = $this->user();

        if ($user && method_exists($user, 'setRememberToken')) {
            $this->provider()->updateRememberToken($user, '');
        }

        $this->session->remove('auth_id');
        $this->session->remove('auth_type');
        CookieJar::forgetCookie('remember_token');
        CookieJar::forgetCookie('remember_id');
        $this->session->regenerate();
        $this->user = null;
    }

    public function logoutCurrentDevice(): void
    {
        $this->session->remove('auth_id');
        $this->session->remove('auth_type');
        $this->session->regenerate();
        $this->user = null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->id();
        if ($id !== null) {
            $user = $this->provider()->retrieveById($id);
            if ($user) {
                $this->user = $user;
                return $user;
            }
        }

        $user = $this->getUserByRememberToken();
        if ($user) {
            $this->user = $user;
            return $user;
        }

        return null;
    }

    public function id(): mixed
    {
        if ($this->user !== null) {
            return $this->user->getAuthIdentifier();
        }

        return $this->session->get('auth_id');
    }

    public function hasRole(string|array $roles): bool
    {
        $user = $this->user();
        if (!$user) return false;

        $userRoles = [];
        if (method_exists($user, 'getRoles')) {
            $userRoles = $user->getRoles();
        } elseif (isset($user->roles)) {
            $userRoles = (array)$user->roles;
        }

        foreach ((array)$roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string|array $permissions): bool
    {
        $user = $this->user();
        if (!$user) return false;

        $userPerms = [];
        if (method_exists($user, 'getPermissions')) {
            $userPerms = $user->getPermissions();
        } elseif (isset($user->permissions)) {
            $userPerms = (array)$user->permissions;
        }

        foreach ((array)$permissions as $perm) {
            if (!in_array($perm, $userPerms, true)) {
                return false;
            }
        }
        return true;
    }

    protected function getUserByRememberToken(): ?Authenticatable
    {
        $token = $_COOKIE['remember_token'] ?? null;
        $identifier = $_COOKIE['remember_id'] ?? null;

        if (!$token || !$identifier) {
            return null;
        }

        return $this->provider()->retrieveByToken((string)$identifier, $token);
    }

    protected function resolveProvider(string $name): UserProvider
    {
        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this);
        }

        return match ($name) {
            'eloquent' => new EloquentUserProvider(
                config('auth.model', config('auth.providers.users.model', 'App\\Models\\User'))
            ),
            default => throw new \Framework\Exception\AuthenticationException("Auth driver [{$name}] is not supported."),
        };
    }
}
