<?php

declare(strict_types=1);

namespace Framework\Plugin;

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
        Hook::addAction('app.booted', function () {
            try {
                $manager = $this->app->make(PluginManager::class);

                $enabled = array_column(
                    \Admin\Models\PluginSetting::where('enabled', true)->get()->all(),
                    'name'
                );

                $manager->boot($enabled);
            } catch (\Throwable $e) {
                // Table may not exist before migration
            }
        }, 0, 0);
    }
}