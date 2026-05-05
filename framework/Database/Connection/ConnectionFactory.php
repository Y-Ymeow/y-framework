<?php

declare(strict_types=1);

namespace Framework\Database\Connection;

use Framework\Database\Contracts\ConnectionInterface;
use PDO;

class ConnectionFactory
{
    /**
     * @param  array{driver: string, host?: string, port?: int, database?: string, username?: string, password?: string, prefix?: string, charset?: string, options?: array<int, mixed>, dsn?: string}  $config
     */
    public function make(array $config): ConnectionInterface
    {
        $driver = $config['driver'] ?? 'mysql';
        $prefix = $config['prefix'] ?? '';

        $pdo = $this->createPdo($config);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return new Connection($pdo, $prefix, $driver);
    }

    /**
     * @param  array{driver: string, host?: string, port?: int, database?: string, username?: string, password?: string, charset?: string, options?: array<int, mixed>, dsn?: string}  $config
     */
    private function createPdo(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $options = $config['options'] ?? [];

        return match ($driver) {
            'mysql' => new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? 3306,
                    $config['database'] ?? '',
                    $config['charset'] ?? 'utf8mb4'
                ),
                $username,
                $password,
                $options
            ),
            'sqlite' => new PDO(
                'sqlite:' . $this->resolveSqlitePath($config['database'] ?? ':memory:'),
                $username,
                $password,
                $options
            ),
            'pgsql' => new PDO(
                sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? 5432,
                    $config['database'] ?? ''
                ),
                $username,
                $password,
                $options
            ),
            default => new PDO(
                $config['dsn'] ?? throw new \InvalidArgumentException("Unsupported driver: {$driver}"),
                $username,
                $password,
                $options
            ),
        };
    }

    private function resolveSqlitePath(string $path): string
    {
        if ($path === ':memory:') {
            return $path;
        }

        if (!str_starts_with($path, '/')) {
            $path = getcwd() . '/' . $path;
        }

        return $path;
    }
}