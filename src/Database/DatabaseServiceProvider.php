<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Foundation\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Connection::class, function () {
            $app = \Framework\Foundation\Application::getInstance();
            $config = include $app->configPath('database.php');
            $default = $config['default'] ?? 'sqlite';
            $dbConfig = $config['connections'][$default] ?? [];
            $conn = Connection::make($dbConfig);

            $logger = $app->make(\Psr\Log\LoggerInterface::class);
            $conn->setLogger($logger);

            Model::setConnection($conn);

            return $conn;
        });

        $this->app->alias(Connection::class, 'db');
    }
}
