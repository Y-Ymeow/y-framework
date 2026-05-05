<?php

declare(strict_types=1);

namespace Framework\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use ReflectionClass;

class Kernel
{
    private Application $application;
    private \Framework\Foundation\Application $app;
    private array $commands = [];

    public function __construct(\Framework\Foundation\Application $app, string $name = 'Y-Framework', string $version = '1.0.0')
    {
        $this->app = $app;
        $this->application = new Application($name, $version);
    }

    public function handle(): int
    {
        $basePath = $this->app->basePath();
        $this->discoverCommands($basePath . '/framework/Console/Commands', 'Framework\\Console\\Commands');
        $this->discoverCommands($basePath . '/app/Commands', 'App\\Commands');

        foreach ($this->commands as $command) {
            $this->application->add($command);
        }

        return $this->application->run();
    }

    private function discoverCommands(string $directory, string $namespace): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $namespace . '\\' . str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($directory) + 1));
            
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Command::class)) {
                continue;
            }

            $constructor = $reflection->getConstructor();
            if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                // 简单的 DI 逻辑：如果构造函数需要 Application，就传进去
                $params = $constructor->getParameters();
                if (count($params) === 1 && ($type = $params[0]->getType()) && $type instanceof \ReflectionNamedType && $type->getName() === \Framework\Foundation\Application::class) {
                    // 我们需要访问 Application 实例。
                    // 假设 Kernel 应该持有 Application 的引用。
                    $this->commands[] = $reflection->newInstance($this->app);
                } else {
                    // 如果不匹配，暂时跳过或尝试无参构造
                    try {
                        $this->commands[] = $reflection->newInstance();
                    } catch (\Throwable) {
                        continue;
                    }
                }
            } else {
                $this->commands[] = $reflection->newInstance();
            }
        }
    }
}
