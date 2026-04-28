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
use Framework\Database\Connection;

#[AsCommand(
    name: 'migrate:rollback',
    description: 'Rollback the last migration batch',
)]
class MigrateRollbackCommand extends Command
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
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Number of migrations to rollback', 0)
            ->addOption('batch', null, InputOption::VALUE_OPTIONAL, 'Batch number to rollback', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Rolling Back Migrations');

        $connection = $this->app->make(Connection::class);
        $migrationsPath = $this->app->basePath('database/migrations');

        $batch = (int)$input->getOption('batch');
        $step = (int)$input->getOption('step');

        $migrations = $this->getMigrationsToRollback($connection, $batch, $step);

        if (empty($migrations)) {
            $io->success('Nothing to rollback.');
            return Command::SUCCESS;
        }

        foreach (array_reverse($migrations) as $migration) {
            $file = $migration['migration'];
            $io->text("Rolling back: {$file}");

            require_once $migrationsPath . '/' . $file;

            $className = $this->getClassNameFromFile($file);
            $migrationInstance = new $className($connection);
            $migrationInstance->down();

            $connection->delete('migrations', 'migration = ?', [$file]);

            $io->text("<info>Rolled back:</info> {$file}");
        }

        $io->newLine();
        $io->success(count($migrations) . ' migration(s) rolled back.');

        return Command::SUCCESS;
    }

    private function getMigrationsToRollback(Connection $connection, int $batch, int $step): array
    {
        if ($batch > 0) {
            return $connection->query(
                "SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC",
                [$batch]
            );
        }

        $lastBatch = $connection->queryOne(
            "SELECT MAX(batch) as max_batch FROM migrations"
        );
        $maxBatch = $lastBatch['max_batch'] ?? 0;

        if ($maxBatch === 0) {
            return [];
        }

        $sql = "SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC";
        $migrations = $connection->query($sql, [$maxBatch]);

        if ($step > 0) {
            return array_slice($migrations, 0, $step);
        }

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
}
