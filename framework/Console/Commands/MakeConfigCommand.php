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
    name: 'make:config',
    description: 'Create a new configuration file',
)]
class MakeConfigCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the config file (e.g. payment)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $name = strtolower(str_replace(['-', ' '], '_', $name));
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Config [{$name}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($name);
        file_put_contents($filePath, $content);

        $io->success("Config [{$name}] created successfully.");
        $io->note("Path: {$filePath}");
        $io->note("Usage: config('{$name}.key')");

        return Command::SUCCESS;
    }

    private function getFilePath(string $name): string
    {
        return $this->app->basePath("config/{$name}.php");
    }

    private function getStub(string $name): string
    {
        $envPrefix = strtoupper($name);

        return <<<PHP
<?php

return [
    'default' => env('{$envPrefix}_DRIVER', 'default'),

    'drivers' => [
        'default' => [
            'key' => env('{$envPrefix}_KEY', ''),
            'options' => [],
        ],
    ],
];
PHP;
    }
}
