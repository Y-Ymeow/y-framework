<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\DebugBar\DebugBar;
use PDO;
use PDOStatement;

class Connection
{
    private ?PDO $pdo = null;
    private string $dsn;
    private string $username;
    private string $password;
    private array $options;
    private string $prefix = '';
    private int $queryCount = 0;
    private array $queries = [];
    private ?\Psr\Log\LoggerInterface $logger = null;

    public function setLogger(\Psr\Log\LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function __construct(string $dsn, string $username = '', string $password = '', array $options = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $options);
    }

    public static function make(array $config): self
    {
        $driver = $config['driver'] ?? 'mysql';
        $prefix = $config['prefix'] ?? '';

        $dsn = match ($driver) {
            'mysql' => "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";dbname={$config['database']};charset=utf8mb4",
            'sqlite' => "sqlite:" . self::resolveSqlitePath($config['database'] ?? ':memory:'),
            'pgsql' => "pgsql:host={$config['host']};port=" . ($config['port'] ?? 5432) . ";dbname={$config['database']}",
            default => $config['dsn'] ?? $config['database'],
        };

        $conn = new self($dsn, $config['username'] ?? '', $config['password'] ?? '', $config['options'] ?? []);
        $conn->prefix = $prefix;
        return $conn;
    }

    private static function resolveSqlitePath(string $path): string
    {
        if ($path === ':memory:') {
            return $path;
        }

        if (!str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        return $path;
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        }
        return $this->pdo;
    }

    public function getDriverName(): string
    {
        return $this->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
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
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => number_format($elapsed, 2) . 'ms',
            'raw_time' => $elapsed,
        ];
        
        if ($this->logger && config('app.debug', false)) {
            $this->logger->debug('Database Query', [
                'sql' => $sql,
                'bindings' => $bindings,
                'time' => number_format($elapsed, 2) . 'ms'
            ]);
        }
        
        return $stmt;
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

    public function insert(string $table, array $data): int
    {
        $table = $this->prefix . $table;
        $columns = implode(', ', array_map(fn($col) => "`{$col}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        return (int)$this->getPdo()->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereBindings = []): int
    {
        $table = $this->prefix . $table;
        $sets = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$sets} WHERE {$where}";
        $stmt = $this->execute($sql, array_merge(array_values($data), $whereBindings));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $bindings = []): int
    {
        $table = $this->prefix . $table;
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $this->prefix . $table);
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->getPdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
