<?php

declare(strict_types=1);

use Framework\Database\Connection;
use Framework\Database\DatabaseManager;
use Framework\Database\QueryBuilder;

if (! function_exists('db')) {
    function db(?string $connection = null): Connection|DatabaseManager
    {
        /** @var DatabaseManager $manager */
        $manager = app(DatabaseManager::class);

        if ($connection === null) {
            return $manager;
        }

        return $manager->connection($connection);
    }
}

if (! function_exists('connection')) {
    function connection(?string $name = null): Connection
    {
        /** @var DatabaseManager $manager */
        $manager = app(DatabaseManager::class);

        return $manager->connection($name);
    }
}

if (! function_exists('table')) {
    function table(string $table, ?string $connection = null): QueryBuilder
    {
        /** @var DatabaseManager $manager */
        $manager = app(DatabaseManager::class);

        return $manager->table($table, $connection);
    }
}

if (! function_exists('sql')) {
    function sql(string $query, array $bindings = [], ?string $connection = null): array
    {
        return connection($connection)->select($query, $bindings);
    }
}
