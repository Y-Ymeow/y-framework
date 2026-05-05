<?php

declare(strict_types=1);

namespace Framework\Module;

use Framework\Foundation\Application;

class ModuleManager
{
    private Application $app;
    private array $modules = [];
    private array $registered = [];
    private bool $booted = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(ModuleInterface $module): void
    {
        $name = $module->getName();

        if (isset($this->modules[$name])) return;

        if (!$module->isEnabled()) return;

        foreach ($module->getDependencies() as $dependency) {
            if (!isset($this->modules[$dependency])) {
                throw new \RuntimeException("Module [{$name}] requires [{$dependency}] but it is not registered.");
            }
        }

        $this->modules[$name] = $module;

        if ($module->getConfigFile() && file_exists($module->getConfigFile())) {
            $moduleConfig = require $module->getConfigFile();
            $key = basename($module->getConfigFile(), '.php');
            $existing = \Framework\Config\ConfigManager::get($key, []);
            \Framework\Config\ConfigManager::set($key, \Framework\Config\ConfigManager::merge($existing, $moduleConfig));
        }

        $providerClass = $module->getServiceProvider();
        if ($providerClass) {
            $provider = $this->app->make($providerClass);
            if ($provider instanceof ModuleServiceProvider) {
                $provider->setModule($module);
            }
            $this->app->register($provider);
        }

        $this->registered[] = $name;
    }

    public function boot(): void
    {
        if ($this->booted) return;

        foreach ($this->modules as $module) {
            $providerClass = $module->getServiceProvider();
            if ($providerClass) {
                $provider = $this->app->make($providerClass);
                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            }
        }

        $this->booted = true;
    }

    public function getModule(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    public function getModules(): array
    {
        return $this->modules;
    }

    public function getRegisteredModules(): array
    {
        return $this->registered;
    }

    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function getMigrations(): array
    {
        $migrations = [];
        foreach ($this->modules as $module) {
            $path = $module->getMigrationsPath();
            if ($path && is_dir($path)) {
                foreach (glob($path . '/*.php') as $file) {
                    $migrations[] = $file;
                }
            }
        }
        return $migrations;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
