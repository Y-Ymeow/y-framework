<?php

declare(strict_types=1);

namespace Framework\Database\Migration;

use Framework\Database\Connection\Manager;
use Framework\Database\Migration\Contracts\MigrationRepositoryInterface;
use Framework\Database\Contracts\ConnectionInterface;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    private Manager $manager;
    private string $table;

    public function __construct(Manager $manager, string $table = 'migrations')
    {
        $this->manager = $manager;
        $this->table = $table;
    }

    public function getRan(): array
    {
        $connection = $this->manager->connection();
        $results = $connection->query("SELECT migration FROM {$this->table} ORDER BY id");

        return array_column($results, 'migration');
    }

    public function getLastBatchNumber(): int
    {
        $connection = $this->manager->connection();
        $result = $connection->queryOne("SELECT MAX(batch) as max_batch FROM {$this->table}");

        return (int)($result['max_batch'] ?? 0);
    }

    public function log(string $migration, int $batch): void
    {
        $connection = $this->manager->connection();
        $connection->insert($this->table, [
            'migration' => $migration,
            'batch' => $batch,
        ]);
    }

    public function delete(string $migration): void
    {
        $connection = $this->manager->connection();
        $connection->delete($this->table, "migration = ?", [$migration]);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->manager->connection();
    }

    public function createRepository(): void
    {
        $connection = $this->manager->connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }

        $connection->execute($sql);
    }
}