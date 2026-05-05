<?php

declare(strict_types=1);

namespace Framework\Database\Facades;

use Framework\Database\Connection\Manager;
use Framework\Database\Contracts\ConnectionInterface;

class DB
{
    private static ?Manager $manager = null;

    public static function setManager(Manager $manager): void
    {
        self::$manager = $manager;
    }

    public static function connection(?string $name = null): ConnectionInterface
    {
        self::ensureManager();

        return self::$manager->connection($name);
    }

    public static function purge(?string $name = null): void
    {
        self::manager()->purge($name);
    }

    public static function switchTo(string $name, array $config): ConnectionInterface
    {
        return self::manager()->switchTo($name, $config);
    }

    private static function ensureManager(): void
    {
        if (self::$manager !== null) {
            return;
        }

        if (!function_exists('app')) {
            throw new \RuntimeException('Database manager not initialized and no container available.');
        }

        $resolved = app(Manager::class);
        if (!$resolved instanceof Manager) {
            throw new \RuntimeException('Container returned non-Manager instance for Manager::class.');
        }

        self::$manager = $resolved;
    }

    private static function manager(): Manager
    {
        self::ensureManager();

        return self::$manager;
    }
}