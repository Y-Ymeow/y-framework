<?php

declare(strict_types=1);

namespace Framework\Plugin;

use Framework\Events\BootEvent;
use Framework\Events\Hook;
use Framework\Foundation\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, function () {
            return new PluginManager($this->app->basePath() . '/plugins');
        });
    }

    public function boot(): void
    {
        Hook::getInstance()->on('app.booting', function (BootEvent $event) {
            try {
                $manager = $this->app->make(PluginManager::class);

                $manager->scan();

                $enabled = array_column(
                    \Admin\Models\PluginSetting::where('enabled', true)->get()->all(),
                    'name'
                );

                $manager->boot($enabled);

                $router = $this->app->make(\Framework\Routing\Router::class);
                \Admin\Services\AdminManager::registerPluginRoutes($router);
            } catch (\Throwable $e) {
                // Table may not exist before migration
            }
        }, 0);
    }
}