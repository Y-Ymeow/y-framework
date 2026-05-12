<?php

declare(strict_types=1);

namespace Framework\Install;

use Framework\Database\Connection\Manager;
use Framework\Database\Migration\DatabaseMigrationRepository;

class InstallManager
{
    public static function isInstalled(): bool
    {
        $env = base_path('.env');
        if (!file_exists($env)) {
            return false;
        }

        $key = env('APP_KEY', '');
        return $key !== '' && $key !== null;
    }

    public static function checkRequirements(): array
    {
        $checks = [];

        $checks[] = self::makeCheck('PHP >= 8.1', PHP_VERSION_ID >= 80100, PHP_VERSION);
        $checks[] = self::makeCheck('PDO Extension', extension_loaded('pdo'));
        $checks[] = self::makeCheck('PDO MySQL Extension', extension_loaded('pdo_mysql'));
        $checks[] = self::makeCheck('PDO SQLite Extension', extension_loaded('pdo_sqlite'));
        $checks[] = self::makeCheck('MBString Extension', extension_loaded('mbstring'));
        $checks[] = self::makeCheck('XML Extension', extension_loaded('xml'));
        $checks[] = self::makeCheck('JSON Extension', extension_loaded('json'));
        $checks[] = self::makeCheck('OpenSSL Extension', extension_loaded('openssl'));
        $checks[] = self::makeCheck('CURL Extension', extension_loaded('curl'));
        $checks[] = self::makeCheck('FileInfo Extension', extension_loaded('fileinfo'));
        $checks[] = self::makeCheck('GD Extension', extension_loaded('gd'));

        $checks[] = self::makeCheck('storage/ writable', is_writable(base_path('storage')));
        $checks[] = self::makeCheck('storage/logs/ writable', is_writable(base_path('storage/logs')));
        $checks[] = self::makeCheck('storage/cache/ writable', is_writable(base_path('storage/cache')));

        return $checks;
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public static function writeEnv(array $config): void
    {
        $driver = $config['driver'] ?? 'mysql';

        $lines = [];
        $lines[] = 'APP_NAME="' . ($config['app_name'] ?? 'Y-Framework') . '"';
        $lines[] = 'APP_ENV=production';
        $lines[] = 'APP_DEBUG=false';
        $lines[] = 'APP_URL=' . ($config['app_url'] ?? 'http://localhost');
        $lines[] = 'APP_KEY=' . ($config['app_key'] ?? self::generateKey());
        $lines[] = '';
        $lines[] = 'DB_CONNECTION=' . $driver;
        $lines[] = 'DB_HOST=' . ($config['db_host'] ?? '127.0.0.1');
        $lines[] = 'DB_PORT=' . ($config['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306'));
        $lines[] = 'DB_DATABASE=' . ($config['db_database'] ?? '');
        $lines[] = 'DB_USERNAME=' . ($config['db_username'] ?? '');
        $lines[] = 'DB_PASSWORD=' . ($config['db_password'] ?? '');
        $lines[] = '';
        $lines[] = 'CACHE_DRIVER=file';
        $lines[] = 'SESSION_DRIVER=file';
        $lines[] = 'LOG_CHANNEL=single';
        $lines[] = 'LOG_LEVEL=debug';
        $lines[] = '';
        $lines[] = 'QUEUE_CONNECTION=database';
        $lines[] = 'QUEUE_TOKEN=' . bin2hex(random_bytes(16));

        file_put_contents(base_path('.env'), implode("\n", $lines) . "\n");

        $_ENV['APP_KEY'] = $config['app_key'] ?? '';
        putenv('APP_KEY=' . ($config['app_key'] ?? ''));
    }

    public static function writeEnvSection(array $config): void
    {
        $driver = $config['driver'] ?? 'mysql';

        $section = [];
        $section[] = 'DB_CONNECTION=' . $driver;
        $section[] = 'DB_HOST=' . ($config['db_host'] ?? '127.0.0.1');
        $section[] = 'DB_PORT=' . ($config['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306'));
        $section[] = 'DB_DATABASE=' . ($config['db_database'] ?? '');
        $section[] = 'DB_USERNAME=' . ($config['db_username'] ?? '');
        $section[] = 'DB_PASSWORD=' . ($config['db_password'] ?? '');

        $env = file_get_contents(base_path('.env'));

        foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
            $env = preg_replace(
                '/^' . preg_quote($key, '/') . '=.*$/m',
                $key . '=' . ($config[strtolower(str_replace('DB_', 'db_', $key))] ?? ''),
                $env
            );
        }

        file_put_contents(base_path('.env'), $env);

        foreach ($section as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $_ENV[$parts[0]] = $parts[1];
                putenv($line);
            }
        }
    }

    public static function runMigrations(Manager $manager): array
    {
        $output = [];
        $repository = new DatabaseMigrationRepository($manager);
        $repository->createRepository();

        $path = base_path('database/migrations');
        if (!is_dir($path)) {
            return ['migrations_path not found'];
        }

        $ran = $repository->getRan();
        $ran = array_map(fn($m) => str_ends_with($m, '.php') ? $m : $m . '.php', $ran);
        $files = glob($path . '/*.php');

        $batch = $repository->getLastBatchNumber() + 1;

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                continue;
            }

            require_once $file;

            $className = self::getClassNameFromFile($name);
            if (!class_exists($className)) {
                $output[] = "SKIP {$name}: class {$className} not found";
                continue;
            }

            $migration = new $className($manager);
            $migration->up();

            $repository->log($name, $batch);

            $output[] = "OK {$name}";
        }

        return $output;
    }

    public static function createAdminUser(Manager $manager, string $email, string $password, string $name = 'Admin'): void
    {
        $conn = $manager->connection();
        $conn->execute(
            "INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))",
            [$name, $email, password_hash($password, PASSWORD_BCRYPT)]
        );
    }

    private static function makeCheck(string $label, bool $passed, string $detail = ''): array
    {
        return [
            'label' => $label,
            'passed' => $passed,
            'detail' => $detail,
        ];
    }

    private static function getClassNameFromFile(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $parts = explode('_', $name);
        $className = '';

        foreach (array_slice($parts, 4) as $part) {
            $className .= ucfirst($part);
        }

        return "Database\\Migrations\\{$className}";
    }
}