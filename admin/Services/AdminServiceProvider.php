<?php

declare(strict_types=1);

namespace Admin\Services;

use Admin\Auth\AuthManager;
use Admin\Auth\EloquentUserProvider;
use Admin\Auth\Gate;
use Admin\Auth\UserProvider;
use Admin\Resources\PostResource;
use Admin\Resources\CategoryResource;
use Admin\Resources\TagResource;
use Admin\Resources\RoleResource;
use Admin\Resources\UserResource;
use Admin\Pages\MenuManagerPage;
use Admin\Pages\MediaPage;
use Admin\Pages\PageBuilderPage;
use Admin\Pages\PluginPage;
use Admin\DashboardData\Register;
use Framework\Foundation\Application;
use Framework\Foundation\ServiceProvider;
use Framework\Lifecycle\LifecycleManager;
use Framework\Http\Middleware\AdminAuthenticate;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LifecycleManager::class, fn() => LifecycleManager::getInstance());

        $this->app->singleton(AuthManager::class, function ($app) {
            $auth = new AuthManager($app->make(\Framework\Http\Session\Session::class));
            $auth->extend('eloquent', function ($auth) {
                return new EloquentUserProvider(\Admin\Auth\User::class);
            });
            return $auth;
        });
        $this->app->alias(AuthManager::class, 'auth');

        $this->app->singleton(Gate::class, function ($app) {
            return new Gate(function ($user) {
                return true;
            });
        });

        $this->app->bind(\Framework\Http\Middleware\AdminAuthenticate::class, function ($app) {
            return new AdminAuthenticate();
        });
    }

    public function boot(): void
    {
        $basePath = $this->app->basePath();

        $prefix = env('ADMIN_PREFIX', '/admin');
        AdminManager::setPrefix($prefix);

        $this->app->make(LifecycleManager::class);

        $adminDirs = [
            $basePath . '/admin/Resources',
            $basePath . '/admin/Pages',
            $basePath . '/admin/Components',
            $basePath . '/admin/DashboardData',
            $basePath . '/admin/Settings',
        ];

        foreach ($adminDirs as $dir) {
            if (is_dir($dir)) {
                LifecycleManager::getInstance()->scanAttributes($dir);
            }
        }

        AdminManager::registerResource(UserResource::class);
        AdminManager::registerResource(RoleResource::class);
        AdminManager::registerPage(MenuManagerPage::class);
        AdminManager::registerPage(MediaPage::class);
        AdminManager::registerPage(PageBuilderPage::class);
        AdminManager::registerPage(PluginPage::class);
        AdminManager::registerResource(PostResource::class);
        AdminManager::registerResource(CategoryResource::class);
        AdminManager::registerResource(TagResource::class);

        Register::boot($basePath);

        AdminManager::registerRoutes($this->app->make(\Framework\Routing\Router::class));
    }
}
