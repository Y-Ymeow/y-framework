<?php

declare(strict_types=1);

namespace Framework\Theme;

use Framework\Foundation\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class, function () {
            return new ThemeManager($this->app->basePath() . '/themes');
        });
    }

    public function boot(): void
    {
    }
}