<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\DebugBar\DebugBar;
use PDO;
use PDOStatement;

class Connection
{
    private static array $connections = [];
    private static string $defaultConnection = 'default';

    private ?PDO $pdo = null;
    private string $dsn;
    private string $username;
    private string $password;
    private array $options;
    private string $prefix = '';
    private int $queryCount = 0;
    private array $queries = [];
    private ?\Psr\Log\LoggerInterface $logger = null;
    private ?string $name = null;

    /**
     * 注册连接配置
     *
     * @param string $name 连接名称
     * @param array $config 连接配置
     */
    public static function register(string $name, array $config): void
    {
        self::$connections[$name] = $config;
    }

    /**
     * 获取连接（自动创建单例）
     *
     * @param string|null $name 连接名称，null 使用默认
     */
    public static function get(?string $name = null): self
    {
        $name = $name ?: self::$defaultConnection;

        if (!isset(self::$connections[$name])) {
            $config = config("database.connections.{$name}");
            if (!$config) {
                throw new \RuntimeException("Database connection [{$name}] is not configured.");
            }
            self::$connections[$name] = $config;
        }

        $key = "__instance__{$name}";
        static $instances = [];

        if (!isset($instances[$key])) {
            $conn = self::make(self::$connections[$name]);
            $conn->name = $name;
            $instances[$key] = $conn;
        }

        return $instances[$key];
    }

    /**
     * 设置默认连接
     */
    public static function setDefault(string $name): void
    {
        self::$defaultConnection = $name;
    }

    /**
     * 动态切换到新连接（用于多租户场景）
     *
     * @param string $name 连接名称
     * @param array $config 连接配置
     * @return self
     */
    public static function switchTo(string $name, array $config): self
    {
        self::$connections[$name] = $config;
        $key = "__instance__{$name}";
        static $instances = [];
        unset($instances[$key]);

        $conn = self::make($config);
        $conn->name = $name;
        $instances[$key] = $conn;
        self::$defaultConnection = $name;

        return $instances[$key];
    }

    /**
     * 切换数据库（同一连接参数，仅改 database 名）
     *
     * @param string $database 新数据库名
     * @param string|null $connection 连接名
     */
    public static function switchDatabase(string $database, ?string $connection = null): self
    {
        $connName = $connection ?: self::$defaultConnection;
        $config = self::$connections[$connName] ?? config("database.connections.{$connName}");

        if (!$config) {
            throw new \RuntimeException("Database connection [{$connName}] is not configured.");
        }

        $config['database'] = $database;
        return self::switchTo($connName, $config);
    }

    /**
     * 清除指定连接实例（强制重新连接）
     */
    public static function purge(?string $name = null): void
    {
        static $instances = [];
        if ($name === null) {
            $instances = [];
        } else {
            unset($instances["__instance__{$name}"]);
        }
    }

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
