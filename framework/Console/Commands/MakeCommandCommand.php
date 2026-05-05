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
    name: 'make:command',
    description: 'Create a new console command',
)]
class MakeCommandCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the command class')
            ->addArgument('command', InputArgument::OPTIONAL, 'The terminal command name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $commandName = $input->getArgument('command') ?: 'app:' . strtolower($name);
        
        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Command [{$name}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($className, $namespace, $commandName);
        file_put_contents($filePath, $content);

        $io->success("Command [{$className}] created successfully.");
        $io->note("Path: {$filePath}");
        $io->note("Command: php bin/console {$commandName}");

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
        return "App\\Commands" . $subNamespace;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return $this->app->basePath("app/Commands/{$name}.php");
    }

    private function getStub(string $className, string $namespace, string $commandName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: '{$commandName}',
    description: 'Command description',
)]
class {$className} extends Command
{
    protected function execute(InputInterface \$input, OutputInterface \$output): int
    {
        \$io = new SymfonyStyle(\$input, \$output);

        \$io->success('Command executed successfully!');

        return Command::SUCCESS;
    }
}
PHP;
    }
}
