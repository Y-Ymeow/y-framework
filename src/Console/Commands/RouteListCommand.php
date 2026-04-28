<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Framework\Foundation\Application;
use Framework\Routing\Router;
use Framework\Routing\SystemRoutesProvider;

#[AsCommand(
    name: 'route:list',
    description: 'List all registered routes',
)]
class RouteListCommand extends Command
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
        $io->title('Registered Routes');

        if (!$this->app->isBooted()) {
            $this->app->instance(Router::class, $this->app->make(Router::class));
            $this->app->bootstrapProviders();
        }

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        
        $basePath = $this->app->basePath();
        $cacheLoaded = $router->loadCache($basePath . '/storage/cache/routes.php');
        
        if (!$cacheLoaded) {
            // 注册系统路由
            SystemRoutesProvider::register($router, $basePath);
            
            // 扫描属性路由
            $scanDirs = config('routes.routes', config('app.scan_dirs', []));
            $dirs = array_map(fn($dir) => $basePath . '/' . ltrim($dir, '/'), (array)$scanDirs);
            $dirs[] = $basePath . '/src/Component';
            $dirs[] = $basePath . '/admin/Pages';
            $dirs = array_unique($dirs);
            $dirs = array_filter($dirs, fn($dir) => is_dir($dir));
            $router->scan($dirs);
        } else {
            // 缓存已加载，不需要再注册系统路由（缓存中已包含）
        }

        $routes = $router->getRoutes();

        if (empty($routes)) {
            $io->warning('No routes found.');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Method', 'Path', 'Name', 'Handler', 'Middleware']);

        foreach ($routes as $route) {
            $handler = is_array($route['handler']) 
                ? ((is_object($route['handler'][0]) ? get_class($route['handler'][0]) : $route['handler'][0]) . '@' . $route['handler'][1])
                : (is_string($route['handler']) ? $route['handler'] : 'Closure');
            
            $middleware = implode(', ', (array)($route['middleware'] ?? []));

            $table->addRow([
                $route['method'],
                $route['path'],
                $route['name'] ?? '',
                $handler,
                $middleware
            ]);
        }

        $table->render();
        $io->newLine();
        $io->note('Total routes: ' . count($routes));

        return Command::SUCCESS;
    }
}
