<?php

declare(strict_types=1);

namespace Framework\Log;

use Framework\Foundation\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LogManager::class, function () {
            $app = \Framework\Foundation\Application::getInstance();
            $config = config('logging');
            return new LogManager($config);
        });

        $this->app->alias(LogManager::class, \Psr\Log\LoggerInterface::class);
        $this->app->alias(LogManager::class, 'log');
    }

    public function boot(): void
    {
        $logger = $this->app->make(LogManager::class);
        \Framework\Error\ErrorHandler::setLogger($logger);
    }
}
