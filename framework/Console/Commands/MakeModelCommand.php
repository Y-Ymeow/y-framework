<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;

#[AsCommand(
    name: 'make:model',
    description: 'Create a new Eloquent-style Model class',
)]
class MakeModelCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model (e.g. User)')
            ->addOption('migration', 'm', \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Also create a migration file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Model [{$className}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tableName = $this->guessTableName($className);
        $content = $this->getStub($className, $namespace, $tableName);
        file_put_contents($filePath, $content);

        $io->success("Model [{$className}] created successfully.");
        $io->note("Path: {$filePath}");

        if ($input->getOption('migration')) {
            $this->createMigration($tableName, $io);
        }

        return Command::SUCCESS;
    }

    private function getClassName(string $name): string
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        return end($parts);
    }

    private function getNamespace(string $name): string
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        array_pop($parts);
        $subNamespace = empty($parts) ? '' : '\\' . implode('\\', $parts);
        return "App\\Models" . $subNamespace;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return $this->app->basePath("app/Models/{$name}.php");
    }

    private function guessTableName(string $className): string
    {
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        if (!str_ends_with($name, 's')) {
            $name .= 's';
        }
        return $name;
    }

    private function createMigration(string $tableName, SymfonyStyle $io): void
    {
        $migrationsPath = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        $filePath = $migrationsPath . '/' . $fileName;

        $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))) . 'Table';

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Migrations;

use Framework\Database\Migration\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        \$this->schema->create('{$tableName}', function (\$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema->drop('{$tableName}');
    }
}
PHP;

        file_put_contents($filePath, $stub);
        $io->note("Migration created: {$fileName}");
    }

    private function getStub(string $className, string $namespace, string $tableName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Framework\Database\Model;

class {$className} extends Model
{
    protected string \$table = '{$tableName}';

    protected array \$fillable = [];

    protected array \$casts = [];
}
PHP;
    }
}
