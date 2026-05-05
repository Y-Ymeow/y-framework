<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;

#[AsCommand(
    name: 'make:migration',
    description: 'Create a new migration file',
)]
class MakeMigrationCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addArgument('table', InputArgument::OPTIONAL, 'The table to migrate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $table = $input->getArgument('table');

        $migrationsPath = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $filePath = $migrationsPath . '/' . $fileName;

        $className = $this->toClassName($name);
        $stub = $this->getStub($className, $table);

        file_put_contents($filePath, $stub);

        $io->success("Migration created: {$fileName}");

        return Command::SUCCESS;
    }

    private function toClassName(string $name): string
    {
        $parts = explode('_', $name);
        $className = '';
        
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        return $className;
    }

    private function getStub(string $className, ?string $table): string
    {
        $table = $table ?? 'table_name';

        if (str_contains(strtolower($className), 'create')) {
            return $this->getCreateStub($className, $table);
        }

        return $this->getDefaultStub($className, $table);
    }

    private function getCreateStub(string $className, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Database\Migrations;

use Framework\Database\Migration\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        \$this->schema->create('{$table}', function (\$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema->drop('{$table}');
    }
}
PHP;
    }

    private function getDefaultStub(string $className, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Database\Migrations;

use Framework\Database\Migration\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
}
PHP;
    }
}
