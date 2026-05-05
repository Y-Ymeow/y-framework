<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Database\Connection\Manager;
use Framework\Database\Schema\Schema;
use Framework\Database\Migration\DatabaseMigrationRepository;

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

        $manager = $this->app->make(Manager::class);
        $repository = new DatabaseMigrationRepository($manager);

        $repository->createRepository();

        if ($input->getOption('fresh')) {
            $io->warning('Dropping all tables...');
            $this->dropAllTables($manager);
            $repository->createRepository();
        }

        $migrationsPath = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        $migrations = $this->getPendingMigrations($repository, $migrationsPath);

        if (empty($migrations)) {
            $io->success('Nothing to migrate.');
            return Command::SUCCESS;
        }

        $step = (int)$input->getOption('step');
        if ($step > 0) {
            $migrations = array_slice($migrations, 0, $step);
        }

        $batch = $repository->getLastBatchNumber() + 1;

        foreach ($migrations as $file) {
            $io->text("Migrating: {$file}");

            require_once $migrationsPath . '/' . $file;

            $className = $this->getClassNameFromFile($file);
            $migration = new $className($manager);
            $migration->up();

            $repository->log($file, $batch);

            $io->text("<info>Migrated:</info> {$file}");
        }

        $io->newLine();
        $io->success(count($migrations) . ' migration(s) completed.');

        if ($input->getOption('seed')) {
            $io->text('Running seeders...');
        }

        return Command::SUCCESS;
    }

    private function getPendingMigrations(DatabaseMigrationRepository $repository, string $path): array
    {
        $ran = $repository->getRan();
        $ranWithExt = array_map(fn($m) => str_ends_with($m, '.php') ? $m : $m . '.php', $ran);
        $files = glob($path . '/*.php');
        $migrations = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $ranWithExt)) {
                $migrations[] = $name;
            }
        }

        sort($migrations);
        return $migrations;
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

    private function dropAllTables(Manager $manager): void
    {
        $connection = $manager->connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
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