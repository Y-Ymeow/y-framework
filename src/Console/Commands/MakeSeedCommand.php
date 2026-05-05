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
    name: 'make:seed',
    description: 'Create a new database seeder class',
)]
class MakeSeedCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the seeder (e.g. UserSeeder)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $className = $this->getClassName($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Seeder [{$className}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($className);
        file_put_contents($filePath, $content);

        $io->success("Seeder [{$className}] created successfully.");
        $io->note("Path: {$filePath}");
        $io->note("Run: php bin/console db:seed {$className}");

        return Command::SUCCESS;
    }

    private function getClassName(string $name): string
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        $last = end($parts);
        if (!str_ends_with($last, 'Seeder')) {
            $last .= 'Seeder';
        }
        return $last;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }
        return $this->app->basePath("database/seeders/{$name}.php");
    }

    private function getStub(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Framework\Database\Contracts\ConnectionInterface;

class {$className}
{
    public function run(ConnectionInterface \$db): void
    {
        \$db->table('table_name')->insert([
            'column' => 'value',
        ]);
    }
}
PHP;
    }
}
