<?php

declare(strict_types=1);

namespace Framework\Admin;

use Framework\Foundation\Application;
use Framework\Foundation\ServiceProvider;
use Framework\Lifecycle\LifecycleManager;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LifecycleManager::class, fn() => LifecycleManager::getInstance());
    }

    public function boot(): void
    {
        $basePath = $this->app->basePath();
        $manager = LifecycleManager::getInstance();

        $resourceDir = $basePath . '/admin/Resources';
        if (is_dir($resourceDir)) {
            $manager->scanAttributes($resourceDir);
        }
    }
}
