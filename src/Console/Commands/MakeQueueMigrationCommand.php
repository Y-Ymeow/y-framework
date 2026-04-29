<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;

#[AsCommand(
    name: 'queue:migration',
    description: 'Create a migration for the queue jobs table',
)]
class MakeQueueMigrationCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $migrationsPath = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_jobs_table.php";
        $filePath = $migrationsPath . '/' . $fileName;

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Migrations;

use Framework\Database\Migration\Migration;

class CreateJobsTable extends Migration
{
    public function up(): void
    {
        \$this->schema->create('jobs', function (\$table) {
            \$table->id();
            \$table->string('queue')->index();
            \$table->string('job_class');
            \$table->text('payload');
            \$table->integer('attempts')->default(0);
            \$table->integer('max_attempts')->default(3);
            \$table->integer('delay')->default(0);
            \$table->integer('run_at')->index();
            \$table->string('status')->default('pending')->index();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema->drop('jobs');
    }
}
PHP;

        file_put_contents($filePath, $stub);

        $io->success("Queue migration created: {$fileName}");

        return Command::SUCCESS;
    }
}
