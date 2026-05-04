<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Foundation\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = config('database') ?? [
            'default' => 'sqlite',
            'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:']],
        ];

        $default = $config['default'] ?? 'sqlite';

        Connection::setDefault($default);

        foreach ($config['connections'] as $name => $connConfig) {
            Connection::register($name, $connConfig);
        }

        $this->app->singleton(Connection::class, function () use ($default) {
            return Connection::get($default);
        });

        $conn = $this->app->make(Connection::class);

        try {
            $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
            $conn->setLogger($logger);
        } catch (\Throwable $e) {
            // Logger 可能未注册，忽略
        }

        Model::setConnection($conn);
    }
}
