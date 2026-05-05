<?php

declare(strict_types=1);

namespace Framework\Database\Contracts;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

interface ConnectionInterface
{
    public function getPdo(): PDO;

    public function getDriverName(): string;

    public function query(string $sql, array $bindings = []): array;

    public function queryOne(string $sql, array $bindings = []): ?array;

    public function execute(string $sql, array $bindings = []): PDOStatement;

    public function table(string $table): QueryBuilderInterface;

    public function transaction(callable $callback): mixed;

    public function insert(string $table, array $data): int;

    public function update(string $table, array $data, string $where, array $whereBindings = []): int;

    public function delete(string $table, string $where, array $bindings = []): int;

    public function getPrefix(): string;

    public function getQueryCount(): int;

    public function getQueries(): array;

    public function getTotalQueryTime(): string;

    public function setLogger(?LoggerInterface $logger): void;

    public function getName(): ?string;
}