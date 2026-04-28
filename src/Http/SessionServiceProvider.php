<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Foundation\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Session::class, fn() => new Session());

        $this->app->alias(Session::class, 'session');
    }

    public function boot(): void
    {
        if (PHP_SAPI !== 'cli') {
            $this->app->make(Session::class)->start();
        }
    }
}
