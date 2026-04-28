<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Database\Connection;
use Framework\Database\Schema\Schema;

#[AsCommand(
    name: 'migrate',
    description: 'Run database migrations',
)]
class MigrateCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this
            ->addOption('fresh', 'f', InputOption::VALUE_NONE, 'Drop all tables and re-run migrations')
            ->addOption('seed', 's', InputOption::VALUE_NONE, 'Run seeders after migration')
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Number of migrations to run', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Running Migrations');

        if (!$this->app->isBooted()) {
            $this->app->bootstrapProviders();
        }

        $connection = $this->app->make(Connection::class);
        $schema = new Schema($connection);

        $this->ensureMigrationsTable($connection);

        if ($input->getOption('fresh')) {
            $io->warning('Dropping all tables...');
            $this->dropAllTables($connection, $schema);
            $this->ensureMigrationsTable($connection);
        }

        $migrationsPath = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        $migrations = $this->getPendingMigrations($connection, $migrationsPath);

        if (empty($migrations)) {
            $io->success('Nothing to migrate.');
            return Command::SUCCESS;
        }

        $step = (int)$input->getOption('step');
        if ($step > 0) {
            $migrations = array_slice($migrations, 0, $step);
        }

        $batch = $this->getNextBatchNumber($connection);

        foreach ($migrations as $file) {
            $io->text("Migrating: {$file}");

            require_once $migrationsPath . '/' . $file;

            $className = $this->getClassNameFromFile($file);
            $migration = new $className($connection);
            $migration->up();

            $connection->insert('migrations', [
                'migration' => $file,
                'batch' => $batch,
            ]);

            $io->text("<info>Migrated:</info> {$file}");
        }

        $io->newLine();
        $io->success(count($migrations) . ' migration(s) completed.');

        if ($input->getOption('seed')) {
            $io->text('Running seeders...');
        }

        return Command::SUCCESS;
    }

    private function ensureMigrationsTable(Connection $connection): void
    {
        if ($connection->getDriverName() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }

        $connection->execute($sql);
    }

    private function getPendingMigrations(Connection $connection, string $path): array
    {
        $ran = $this->getRanMigrations($connection);
        $files = glob($path . '/*.php');
        $migrations = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $ran)) {
                $migrations[] = $name;
            }
        }

        sort($migrations);
        return $migrations;
    }

    private function getRanMigrations(Connection $connection): array
    {
        $results = $connection->query("SELECT migration FROM migrations ORDER BY id");
        return array_column($results, 'migration');
    }

    private function getNextBatchNumber(Connection $connection): int
    {
        $result = $connection->queryOne("SELECT MAX(batch) as max_batch FROM migrations");
        return ($result['max_batch'] ?? 0) + 1;
    }

    private function getClassNameFromFile(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $parts = explode('_', $name);
        $className = '';
        
        foreach (array_slice($parts, 4) as $part) {
            $className .= ucfirst($part);
        }
        
        return "Database\\Migrations\\{$className}";
    }

    private function dropAllTables(Connection $connection, Schema $schema): void
    {
        if ($connection->getDriverName() === 'sqlite') {
            $tables = $connection->query("SELECT name AS table_name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        } else {
            $tables = $connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
        }

        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            if ($tableName !== 'migrations') {
                $connection->execute("DROP TABLE IF EXISTS \"{$tableName}\"");
            }
        }

        $connection->execute("DROP TABLE IF EXISTS migrations");
    }
}
