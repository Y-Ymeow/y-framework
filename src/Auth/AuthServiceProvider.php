<?php

declare(strict_types=1);

namespace Framework\Auth;

use Framework\Foundation\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthManager::class, function () {
            $app = \Framework\Foundation\Application::getInstance();
            return new AuthManager(
                $app->make(\Framework\Http\Session::class),
                $app->make(\Framework\Database\Connection::class)
            );
        });

        $this->app->alias(AuthManager::class, 'auth');
    }
}
