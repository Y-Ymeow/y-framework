<?php

declare(strict_types=1);

namespace Framework\Module\User;

use Framework\Auth\AuthManager;
use Framework\Auth\Gate;
use Framework\Module\ModuleServiceProvider;

class UserServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthManager::class, function () {
            return new AuthManager(
                $this->app->make(\Framework\Http\Session\Session::class)
            );
        });

        $this->app->alias(AuthManager::class, 'auth');
        $this->app->singleton(Gate::class, fn () => Gate::getInstance());
    }

    public function boot(): void
    {
        if (config('auth.gates')) {
            foreach (config('auth.gates', []) as $ability => $callback) {
                if ($callback instanceof \Closure) {
                    Gate::defineStatic($ability, $callback);
                } elseif (is_string($callback) && class_exists($callback)) {
                    Gate::defineStatic($ability, fn (...$args) => (new $callback)(...$args));
                }
            }
        }

        if (config('auth.policies')) {
            foreach (config('auth.policies', []) as $model => $policy) {
                Gate::policyStatic($model, $policy);
            }
        }
    }
}
