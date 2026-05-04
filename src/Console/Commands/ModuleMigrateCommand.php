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
use Framework\Module\ModuleManager;

#[AsCommand(
    name: 'module:migrate',
    description: 'Run migrations for registered modules',
)]
class ModuleMigrateCommand extends Command
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
            ->addArgument('module', InputArgument::OPTIONAL, 'Module name to migrate (omit for all)')
            ->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Rollback module migrations')
            ->addOption('fresh', 'f', InputOption::VALUE_NONE, 'Drop and re-run module migrations')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List registered modules and their migration status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->app->isBooted()) {
            $this->app->bootstrapProviders();
        }

        $moduleManager = $this->app->getModuleManager();
        if ($moduleManager === null) {
            $io->error('ModuleManager is not initialized.');
            return Command::FAILURE;
        }

        if ($input->getOption('list')) {
            return $this->listModules($moduleManager, $io);
        }

        $moduleName = $input->getArgument('module');
        $connection = $this->app->make(Connection::class);

        $this->ensureMigrationsTable($connection);

        if ($moduleName) {
            $module = $moduleManager->getModule($moduleName);
            if ($module === null) {
                $io->error("Module [{$moduleName}] is not registered.");
                $io->text('Registered modules: ' . implode(', ', $moduleManager->getRegisteredModules()));
                return Command::FAILURE;
            }
            $modules = [$module];
        } else {
            $modules = $moduleManager->getModules();
        }

        if ($input->getOption('rollback')) {
            return $this->rollbackModules($modules, $connection, $io);
        }

        if ($input->getOption('fresh')) {
            return $this->freshModules($modules, $connection, $io);
        }

        return $this->runModules($modules, $connection, $io);
    }

    private function listModules(ModuleManager $manager, SymfonyStyle $io): int
    {
        $io->title('Registered Modules');

        $modules = $manager->getModules();
        if (empty($modules)) {
            $io->text('No modules registered.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($modules as $module) {
            $migrationPath = $module->getMigrationsPath();
            $migrationCount = 0;
            if ($migrationPath && is_dir($migrationPath)) {
                $migrationCount = count(glob($migrationPath . '/*.php'));
            }

            $rows[] = [
                $module->getName(),
                $module->isEnabled() ? '<info>Yes</info>' : '<comment>No</comment>',
                $module->getServiceProvider() ?? '-',
                $migrationCount,
                implode(', ', $module->getDependencies()) ?: '-',
            ];
        }

        $io->table(
            ['Module', 'Enabled', 'ServiceProvider', 'Migrations', 'Dependencies'],
            $rows
        );

        return Command::SUCCESS;
    }

    private function runModules(array $modules, Connection $connection, SymfonyStyle $io): int
    {
        $io->title('Running Module Migrations');

        $total = 0;

        foreach ($modules as $module) {
            $migrationPath = $module->getMigrationsPath();
            if (!$migrationPath || !is_dir($migrationPath)) {
                $io->text("<comment>Module [{$module->getName()}] has no migrations.</comment>");
                continue;
            }

            $io->section("Module: {$module->getName()}");

            $pending = $this->getPendingMigrations($connection, $migrationPath, $module->getName());

            if (empty($pending)) {
                $io->text("Nothing to migrate for [{$module->getName()}].");
                continue;
            }

            $batch = $this->getNextBatchNumber($connection);

            foreach ($pending as $file) {
                $io->text("Migrating: {$file}");

                require_once $migrationPath . '/' . $file;

                $className = $this->getClassNameFromFile($file, $migrationPath);
                if ($className === null) {
                    $io->text("<error>Could not resolve class for: {$file}</error>");
                    continue;
                }

                $migration = new $className($connection);
                $migration->up();

                $connection->insert('migrations', [
                    'migration' => $this->getModuleMigrationKey($module->getName(), $file),
                    'batch' => $batch,
                ]);

                $io->text("<info>Migrated:</info> {$file}");
                $total++;
            }
        }

        $io->newLine();
        $io->success("{$total} module migration(s) completed.");

        return Command::SUCCESS;
    }

    private function rollbackModules(array $modules, Connection $connection, SymfonyStyle $io): int
    {
        $io->title('Rolling Back Module Migrations');

        $total = 0;

        foreach ($modules as $module) {
            $migrationPath = $module->getMigrationsPath();
            if (!$migrationPath || !is_dir($migrationPath)) {
                continue;
            }

            $io->section("Module: {$module->getName()}");

            $ran = $this->getModuleRanMigrations($connection, $module->getName());

            if (empty($ran)) {
                $io->text("Nothing to rollback for [{$module->getName()}].");
                continue;
            }

            foreach (array_reverse($ran) as $record) {
                $key = $record['migration'];
                $file = $this->extractFileFromKey($key);

                $io->text("Rolling back: {$file}");

                $fullPath = $migrationPath . '/' . $file;
                if (!file_exists($fullPath)) {
                    $io->text("<error>Migration file not found: {$file}</error>");
                    continue;
                }

                require_once $fullPath;

                $className = $this->getClassNameFromFile($file, $migrationPath);
                if ($className === null) {
                    $io->text("<error>Could not resolve class for: {$file}</error>");
                    continue;
                }

                $migration = new $className($connection);
                $migration->down();

                $connection->delete('migrations', 'migration = ?', [$key]);

                $io->text("<info>Rolled back:</info> {$file}");
                $total++;
            }
        }

        $io->newLine();
        $io->success("{$total} module migration(s) rolled back.");

        return Command::SUCCESS;
    }

    private function freshModules(array $modules, Connection $connection, SymfonyStyle $io): int
    {
        $io->title('Fresh Module Migrations');

        $this->rollbackModules($modules, $connection, $io);

        $io->newLine();

        return $this->runModules($modules, $connection, $io);
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

    private function getPendingMigrations(Connection $connection, string $path, string $moduleName): array
    {
        $ran = $this->getModuleRanMigrations($connection, $moduleName);
        $ranFiles = array_map(fn($r) => $this->extractFileFromKey($r['migration']), $ran);

        $files = glob($path . '/*.php');
        $pending = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $ranFiles)) {
                $pending[] = $name;
            }
        }

        sort($pending);
        return $pending;
    }

    private function getModuleRanMigrations(Connection $connection, string $moduleName): array
    {
        $prefix = "module:{$moduleName}:";
        $results = $connection->query(
            "SELECT * FROM migrations WHERE migration LIKE ? ORDER BY id",
            [$prefix . '%']
        );
        return $results;
    }

    private function getModuleMigrationKey(string $moduleName, string $file): string
    {
        return "module:{$moduleName}:{$file}";
    }

    private function extractFileFromKey(string $key): string
    {
        $parts = explode(':', $key, 3);
        return $parts[2] ?? $key;
    }

    private function getNextBatchNumber(Connection $connection): int
    {
        $result = $connection->queryOne("SELECT MAX(batch) as max_batch FROM migrations");
        return ($result['max_batch'] ?? 0) + 1;
    }

    private function getClassNameFromFile(string $file, string $migrationPath): ?string
    {
        $fullPath = $migrationPath . '/' . $file;
        if (!file_exists($fullPath)) return null;

        $contents = file_get_contents($fullPath);

        if (preg_match('/namespace\s+([\w\\\\]+)\s*;/', $contents, $namespaceMatch) &&
            preg_match('/class\s+(\w+)\s+extends/', $contents, $classMatch)) {
            return $namespaceMatch[1] . '\\' . $classMatch[1];
        }

        if (preg_match('/class\s+(\w+)\s+extends/', $contents, $classMatch)) {
            return $classMatch[1];
        }

        return null;
    }
}
