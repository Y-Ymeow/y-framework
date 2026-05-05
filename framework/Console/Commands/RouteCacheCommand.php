<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Routing\Router;


#[AsCommand(
    name: 'route:cache',
    description: 'Create a route cache file for faster boot',
)]
class RouteCacheCommand extends Command
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
        $io->title('Route Cache Compiler');

        // 先启动 ServiceProviders 注册应用路由
        $this->app->bootstrapProviders();
        $router = $this->app->make(Router::class);
        
        // 扫描属性路由
        $basePath = $this->app->basePath();
        $scanDirs = config('routes.routes', []);
        $dirs = array_map(fn($dir) => $basePath . '/' . ltrim($dir, '/'), (array)$scanDirs);
        $dirs = array_filter($dirs, fn($dir) => is_dir($dir));

        $frameworkDir = paths()->frameworkSrc();
        $files = [
            $frameworkDir . '/Component/Live/LiveComponentResolver.php',
            $frameworkDir . '/Routing/SystemRoute.php',
        ];

        $io->info('Scanning directories: ' . implode(', ', $dirs));

        if (!empty($dirs)) {
            $router->scan($dirs, $files);
        }
        
        $routes = $router->getRoutes();
        $count = count($routes);
        
        $cachePath = paths()->cache('routes.php');
        $dir = dirname($cachePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $content = "<?php\n\nreturn " . var_export($routes, true) . ";\n";
        file_put_contents($cachePath, $content);

        $io->success("Cached {$count} routes successfully!");
        $io->note("Cache path: {$cachePath}");

        return Command::SUCCESS;
    }
}
