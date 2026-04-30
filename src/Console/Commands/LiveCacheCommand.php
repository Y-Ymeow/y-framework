<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Support\Finder;

#[AsCommand(
    name: 'live:cache',
    description: 'Create a cache for Live Component actions',
)]
class LiveCacheCommand extends Command
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
        $io->title('Live Component Action Cache Compiler');

        $basePath = $this->app->basePath();
        $dirs = [
            $basePath . '/app/Components',
            $basePath . '/admin/Pages',
        ];
        $dirs = array_filter($dirs, fn($dir) => is_dir($dir));

        $finder = new Finder();
        $finder->files()->in($dirs)->name('*.php');

        $cache = [];
        foreach ($finder as $file) {
            $classes = $this->getClassesFromFile($file->getRealPath());
            foreach ($classes as $className) {
                if (!class_exists($className)) continue;

                $reflection = new \ReflectionClass($className);
                if ($reflection->isAbstract() || !$reflection->isSubclassOf(LiveComponent::class)) {
                    continue;
                }

                $actions = [];
                foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    $attrs = $method->getAttributes(LiveAction::class);
                    if (!empty($attrs)) {
                        $attr = $attrs[0]->newInstance();
                        $name = $attr->name ?? $method->getName();
                        $actions[$name] = $method->getName();
                    }
                }

                if (!empty($actions)) {
                    $cache[$className] = $actions;
                }
            }
        }

        $cachePath = $this->app->basePath('storage/cache/live_components.php');
        $dir = dirname($cachePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $content = "<?php\n\nreturn " . var_export($cache, true) . ";\n";
        file_put_contents($cachePath, $content);

        $io->success("Cached actions for " . count($cache) . " components!");
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
