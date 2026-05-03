<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Foundation\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = config('database');
        $default = $config['default'] ?? 'sqlite';

        Connection::setDefault($default);

        foreach ($config['connections'] as $name => $connConfig) {
            Connection::register($name, $connConfig);
        }

        $conn = Connection::get($default);

        $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
        $conn->setLogger($logger);

        Model::setConnection($conn);
    }
}
