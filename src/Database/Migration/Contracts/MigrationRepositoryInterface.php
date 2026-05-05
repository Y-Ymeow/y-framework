<?php

declare(strict_types=1);

namespace Framework\Database\Migration\Contracts;

interface MigrationRepositoryInterface
{
    public function getRan(): array;

    public function getLastBatchNumber(): int;

    public function log(string $migration, int $batch): void;

    public function delete(string $migration): void;

    public function createRepository(): void;
}