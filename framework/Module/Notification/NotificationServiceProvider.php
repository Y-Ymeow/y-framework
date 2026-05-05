<?php

declare(strict_types=1);

namespace Framework\Module\Notification;

use Framework\Module\ModuleServiceProvider;

class NotificationServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationManager::class, function () {
            return new NotificationManager();
        });

        $this->app->alias(NotificationManager::class, 'notification');
    }

    public function boot(): void
    {
    }
}
