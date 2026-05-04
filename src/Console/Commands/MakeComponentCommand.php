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
    name: 'make:component',
    description: 'Create a new Live Component',
)]
class MakeComponentCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the component (e.g. UserList)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        
        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Component [{$name}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($className, $namespace);
        file_put_contents($filePath, $content);

        $io->success("Component [{$className}] created successfully.");
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
        return "App\\Components" . $subNamespace;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return $this->app->basePath("app/Components/{$name}.php");
    }

    private function getStub(string $className, string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\LiveResponse;
use Framework\View\Base\Element;
use Framework\View\Element\Container;
use Framework\View\Element\Text;

class {$className} extends LiveComponent
{
    public int \$count = 0;

    #[LiveAction]
    public function increment(): LiveResponse
    {
        \$this->count++;
        return LiveResponse::make();
    }

    public function render(): Element
    {
        return Container::make()
            ->class('p-4 border rounded shadow-sm bg-white')
            ->child(Text::p("Count: {\$this->count}")->class('text-lg font-bold'))
            ->child(
                (new Element('button'))
                    ->liveAction('increment')
                    ->class('mt-2 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600')
                    ->text('Increment')
            );
    }
}
PHP;
    }
}
