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
    name: 'make:event-listener',
    description: 'Create a new event listener class',
)]
class MakeEventListenerCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the event listener (e.g. SendWelcomeEmail)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Event listener [{$className}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($className, $namespace);
        file_put_contents($filePath, $content);

        $io->success("Event listener [{$className}] created successfully.");
        $io->note("Path: {$filePath}");

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
        return "App\\Listeners" . $subNamespace;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return $this->app->basePath("app/Listeners/{$name}.php");
    }

    private function getStub(string $className, string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    public function handle(mixed \$data = null): void
    {
        // 事件处理逻辑
    }
}
PHP;
    }
}
