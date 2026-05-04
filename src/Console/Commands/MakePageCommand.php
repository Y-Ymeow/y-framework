<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;

#[AsCommand(
    name: 'make:page',
    description: 'Create a new Page (Live Component with route)',
)]
class MakePageCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the page (e.g. UserProfile)')
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'The route path', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $route = $input->getOption('route') ?: '/' . strtolower($name);
        
        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Page [{$name}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($className, $namespace, $route);
        file_put_contents($filePath, $content);

        $io->success("Page [{$className}] created successfully.");
        $io->note("Path: {$filePath}");
        $io->note("Route: {$route}");

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
        return "App\\Pages" . $subNamespace;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return $this->app->basePath("app/Pages/{$name}.php");
    }

    private function getStub(string $className, string $namespace, string $route): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Container;
use Framework\View\Text;

class {$className}
{
    #[Route('{$route}', methods: ['GET'])]
    public function index(): Response
    {
        \$doc = Container::make()
            ->class('p-8 max-w-4xl mx-auto')
            ->child(Text::h1('{$className}')->class('text-3xl font-bold mb-4'))
            ->child(Text::p('This is a newly created page.')->textGray());

        return Response::html(\$doc);
    }
}
PHP;
    }
}
