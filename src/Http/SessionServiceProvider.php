<?php

declare(strict_types=1);

namespace Framework\Http;

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
        if (PHP_SAPI !== 'cli') {
            // 启动 PHP 原生 session，确保 $_SESSION 可用并与旧系统互通
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $this->app->make(Session::class)->start();
        }
    }
}
