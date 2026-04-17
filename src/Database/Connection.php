<?php

declare(strict_types=1);

namespace Framework\Database;

use PDO;
use PDOStatement;

final class Connection
{
    private ?PDO $pdo = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $options = $this->config['options'] ?? [];
        $options[PDO::ATTR_ERRMODE] ??= PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] ??= PDO::FETCH_ASSOC;
        $options[PDO::ATTR_EMULATE_PREPARES] ??= false;

        $this->pdo = new PDO(
            $this->config['dsn'],
            $this->config['username'] ?? null,
            $this->config['password'] ?? null,
            $options,
        );

        return $this->pdo;
    }

    /**
     * @param array<int|string, mixed> $bindings
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->run($sql, $bindings)->fetchAll();
    }

    /**
     * @param array<int|string, mixed> $bindings
     */
    public function first(string $sql, array $bindings = []): ?array
    {
        $row = $this->run($sql, $bindings)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string, mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): bool
    {
        return $this->run($sql, $bindings)->rowCount() >= 0;
    }

    /**
     * @param array<int|string, mixed> $bindings
     */
    public function statement(string $sql, array $bindings = []): PDOStatement
    {
        return $this->run($sql, $bindings);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo()->rollBack();
    }

    /**
     * @param callable(self): mixed $callback
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->pdo()->inTransaction()) {
                $this->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<int|string, mixed> $bindings
     */
    private function run(string $sql, array $bindings = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);

        foreach ($bindings as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? (string) $key : ':' . $key);
            $statement->bindValue($parameter, $value);
        }

        $statement->execute();

        return $statement;
    }
}
