<?php

declare(strict_types=1);

namespace Framework\Http\Session;

use Framework\Foundation\ServiceProvider;
use Framework\Http\Session\Session;

class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->alias(Session::class, 'session');
    }

    public function boot(): void
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $this->app->make(Session::class)->start();
        }
    }
}
