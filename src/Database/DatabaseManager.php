<?php

declare(strict_types=1);

namespace Framework\Database;

use RuntimeException;

final class DatabaseManager
{
    /**
     * @var array<string, Connection>
     */
    private array $connections = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function connection(?string $name = null): Connection
    {
        $default = $name ?? (string) ($this->config['default'] ?? 'default');

        if (isset($this->connections[$default])) {
            return $this->connections[$default];
        }

        $connections = $this->config['connections'] ?? [];

        if (! isset($connections[$default]) || ! is_array($connections[$default])) {
            throw new RuntimeException("Database connection [{$default}] is not configured.");
        }

        return $this->connections[$default] = new Connection($connections[$default]);
    }

    public function table(string $table, ?string $connection = null): QueryBuilder
    {
        return new QueryBuilder($this->connection($connection), $table);
    }
}
