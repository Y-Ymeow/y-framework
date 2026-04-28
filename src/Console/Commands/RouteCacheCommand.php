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
use Framework\Routing\SystemRoutesProvider;

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
        
        // 注册系统路由
        $basePath = $this->app->basePath();
        SystemRoutesProvider::register($router, $basePath);

        // 再扫描属性路由
        $scanDirs = config('routes.routes', config('app.scan_dirs', []));
        $dirs = array_map(fn($dir) => $basePath . '/' . ltrim($dir, '/'), (array)$scanDirs);
        $dirs[] = $basePath . '/src/Component';
        $dirs[] = $basePath . '/admin/Pages';
        
        $dirs = array_unique($dirs);
        $dirs = array_filter($dirs, fn($dir) => is_dir($dir));
        
        $io->info('Scanning directories: ' . implode(', ', $dirs));
        
        $finder = new \Framework\Support\Finder();
        $files = $finder->files()->in($dirs)->name('*.php')->getIterator();
        $io->note('Found ' . count($files) . ' PHP files');

        foreach ($files as $file) {
            $classes = $this->getClassesFromFile($file->getRealPath());
            foreach ($classes as $className) {
                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    $router->registerClass($reflection);
                }
            }
        }
        
        $routes = $router->getRoutes();
        $count = count($routes);
        
        $cachePath = $this->app->basePath('storage/cache/routes.php');
        $dir = dirname($cachePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $content = "<?php\n\nreturn " . var_export($routes, true) . ";\n";
        file_put_contents($cachePath, $content);

        $io->success("Cached {$count} routes successfully!");
        $io->note("Cache path: {$cachePath}");

        return Command::SUCCESS;
    }

    private function getClassesFromFile(string $filePath): array
    {
        $classes = [];
        $content = file_get_contents($filePath);
        $tokens = token_get_all($content);
        $namespace = '';
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            // 捕获命名空间
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === ';') break;
                    if (is_array($tokens[$j])) {
                        // 支持 T_STRING, T_NS_SEPARATOR 以及 PHP 8+ 的 T_NAME_QUALIFIED
                        if (in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true)) {
                            $namespace .= $tokens[$j][1];
                        }
                    }
                }
                $i = $j;
                continue;
            }

            // 捕获类名
            if (is_array($token) && $token[0] === T_CLASS) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{') break;
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                        $classes[] = ($namespace ? $namespace . '\\' : '') . $className;
                        break;
                    }
                }
            }
        }
        return $classes;
    }
}
