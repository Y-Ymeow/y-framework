<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Database\Connection\Manager;
use Framework\Database\Contracts\ConnectionInterface;
use Framework\Database\Facades\DB;
use Framework\Foundation\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Manager::class, function () {
            return new Manager();
        });

        $this->app->singleton(ConnectionInterface::class, function ($app) {
            return $app->make(Manager::class)->connection();
        });

        $manager = $this->app->make(Manager::class);
        DB::setManager($manager);
    }

    public function boot(): void
    {
        $config = config('database');
        if ($config === null) {
            return;
        }

        $default = $config['default'] ?? 'sqlite';
        $manager = $this->app->make(Manager::class);
        $manager->setDefaultName($default);

        Model::setConnectionResolver(function () {
            return app(ConnectionInterface::class);
        });

        try {
            $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
            $conn = $manager->connection();
            $conn->setLogger($logger);
        } catch (\Throwable) {
        }
    }
}