<?php

declare(strict_types=1);

namespace Admin\Auth;

use Framework\Foundation\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthManager::class, function () {
            return new AuthManager(
                $this->app->make(\Framework\Http\Session\Session::class)
            );
        });

        $this->app->alias(AuthManager::class, 'auth');
    }

    public function boot(): void
    {
        // 注册认证相关的 gates 和 policies
        if (config('auth.gates')) {
            foreach (config('auth.gates', []) as $ability => $callback) {
                if ($callback instanceof \Closure) {
                    \Framework\Auth\Gate::defineStatic($ability, $callback);
                } elseif (is_string($callback) && class_exists($callback)) {
                    \Framework\Auth\Gate::defineStatic($ability, fn (...$args) => (new $callback)(...$args));
                }
            }
        }

        if (config('auth.policies')) {
            foreach (config('auth.policies', []) as $model => $policy) {
                \Framework\Auth\Gate::policyStatic($model, $policy);
            }
        }
    }
}
