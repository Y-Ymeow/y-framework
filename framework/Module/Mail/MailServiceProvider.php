<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

use Framework\Module\ModuleServiceProvider;

class MailServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailManager::class, function () {
            return new MailManager(config('mail', []));
        });

        $this->app->alias(MailManager::class, 'mail');
    }
}
