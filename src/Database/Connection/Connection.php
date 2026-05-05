<?php

declare(strict_types=1);

namespace Framework\Database\Connection;

use Framework\Database\Contracts\ConnectionInterface;
use Framework\Database\Contracts\QueryBuilderInterface;
use Framework\Database\Query\Builder;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class Connection implements ConnectionInterface
{
    private int $queryCount = 0;

    /** @var array<int, array{sql: string, bindings: array, time: string, raw_time: float}> */
    private array $queries = [];

    private ?LoggerInterface $logger = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $prefix = '',
        private readonly string $driver = 'mysql',
        private readonly ?string $name = null,
    ) {}

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getDriverName(): string
    {
        return $this->driver;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->execute($sql, $bindings);
        return $stmt->fetchAll();
    }

    public function queryOne(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->execute($sql, $bindings);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function execute(string $sql, array $bindings = []): PDOStatement
    {
        $this->queryCount++;
        $start = microtime(true);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        $elapsed = (microtime(true) - $start) * 1000;

        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => number_format($elapsed, 2) . 'ms',
            'raw_time' => $elapsed,
        ];

        if ($this->logger !== null) {
            $this->logger->debug('Database Query', [
                'sql' => $sql,
                'bindings' => $bindings,
                'time' => number_format($elapsed, 2) . 'ms',
            ]);
        }

        return $stmt;
    }

    public function table(string $table): QueryBuilderInterface
    {
        $grammarClass = match ($this->driver) {
            'sqlite' => \Framework\Database\Query\Grammars\SqliteGrammar::class,
            'pgsql' => \Framework\Database\Query\Grammars\PostgresGrammar::class,
            default => \Framework\Database\Query\Grammars\MySqlGrammar::class,
        };

        $grammar = new $grammarClass();
        return new Builder($this, $this->prefix . $table, $grammar);
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        $backtick = $this->driver === 'sqlite' ? '"' : '`';
        $escaped = str_replace($backtick, $backtick . $backtick, $identifier);
        return $backtick . $escaped . $backtick;
    }

    public function insert(string $table, array $data): int
    {
        $table = $this->quoteIdentifier($this->prefix . $table);
        $columns = implode(', ', array_map(fn(string $col): string => $this->quoteIdentifier($col), array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereBindings = []): int
    {
        $table = $this->quoteIdentifier($this->prefix . $table);
        $sets = implode(', ', array_map(fn(string $col): string => $this->quoteIdentifier($col) . ' = ?', array_keys($data)));
        $sql = "UPDATE {$table} SET {$sets} WHERE {$where}";
        $stmt = $this->execute($sql, array_merge(array_values($data), $whereBindings));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $bindings = []): int
    {
        $table = $this->quoteIdentifier($this->prefix . $table);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getTotalQueryTime(): string
    {
        $total = array_sum(array_column($this->queries, 'raw_time'));
        return number_format($total, 2) . 'ms';
    }
}