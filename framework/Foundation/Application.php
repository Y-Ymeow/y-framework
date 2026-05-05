<?php

declare(strict_types=1);

namespace Framework\Foundation;

use Framework\Http\Request\Request;
use Framework\Module\ModuleManager;
use Framework\Support\Paths;

class Application
{
    private string $basePath;
    private Paths $paths;
    private Container $container;
    private bool $booted = false;
    private array $providers = [];
    private ?ModuleManager $moduleManager = null;
    private static ?Application $instance = null;
    private static bool $isDebug = false;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->paths = new Paths($this->basePath);
        self::$instance = $this;
        $GLOBALS['app'] = $this;

        if (file_exists($this->basePath . '/.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        }

        $this->container = new Container();

        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);
        $this->container->alias(self::class, 'app');
        $this->container->instance('base_path', $this->basePath);
        $this->container->singleton(
            Request::class,
            fn() => Request::createFromGlobals()
        );

        // 立即加载配置，确保 Provider 注册前 config() 可用
        \Framework\Config\ConfigManager::load();
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public static function isDebug(): bool
    {
        return self::$isDebug;
    }

    public static function setDebug(bool $isDebug): void
    {
        self::$isDebug = $isDebug;
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    public function bind(string $abstract, mixed $concrete = null): void
    {
        $this->container->bind($abstract, $concrete);
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->container->alias($abstract, $alias);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . $path : '');
    }

    public function paths(): Paths
    {
        return $this->paths;
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath . '/storage' . ($path ? '/' . $path : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath . '/config' . ($path ? '/' . $path : '');
    }

    public function make(string $class): mixed
    {
        return $this->container->get($class);
    }

    public function makeWith(string $class, array $parameters = []): mixed
    {
        return $this->container->makeWith($class, $parameters);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->container->instance($abstract, $instance);
    }

    public function register(ServiceProvider $provider): void
    {
        $name = get_class($provider);
        if (isset($this->providers[$name])) return;

        $this->providers[$name] = $provider;
        $provider->register();

        if ($this->booted) {
            $provider->boot();
        }
    }

    public function boot(): void
    {
        if ($this->booted) return;

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function bootstrapProviders(): void
    {
        $this->moduleManager = new ModuleManager($this);
        $this->container->instance(ModuleManager::class, $this->moduleManager);

        // 1. 自动扫描 app/ 目录，注册用户 Module
        $this->registerUserModules();

        // 2. 注册用户 ServiceProvider（来自 config/app.php）
        $providers = config('app.providers', []);
        foreach ($providers as $providerClass) {
            $this->register(new $providerClass($this));
        }

        // 3. 注册 Module（来自 config/app.php）
        $this->registerUserModulesFromConfig();

        if (self::isDebug()) {
            $debugProviders = config('app.debug_providers', []);
            foreach ($debugProviders as $providerClass) {
                $this->register(new $providerClass($this));
            }
        }

        $this->make(\Framework\Intl\IntlServiceProvider::class)->register();

        $this->boot();

        $this->moduleManager->boot();

        // 4. 扫描 app/ 目录中的 Attribute 路由
        $this->scanAttributeRoutes();
    }

    /**
     * 自动扫描 app/Modules 目录，注册用户 Module
     */
    private function registerUserModules(): void
    {
        $appModulesPath = $this->basePath('/app/Modules');
        if (!is_dir($appModulesPath)) return;

        foreach (glob($appModulesPath . '/*Module.php') as $file) {
            $class = $this->getModuleClassFromFile($file);
            if ($class && is_subclass_of($class, \Framework\Module\BaseModule::class)) {
                $this->moduleManager->register(new $class());
            }
        }
    }

    /**
     * 从配置文件注册 Module
     */
    private function registerUserModulesFromConfig(): void
    {
        $modules = config('app.modules', []);

        foreach ($modules as $moduleClass) {
            $this->moduleManager->register(new $moduleClass());
        }
    }

    /**
     * 从文件获取 Module 类名
     */
    private function getModuleClassFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);
        if (preg_match('/namespace\\s+([^;]+);/', $contents, $matches)) {
            $namespace = trim($matches[1]);
        } else {
            return null;
        }

        // 提取文件名中的类名
        $fileName = basename($file, '.php');
        
        return $namespace . '\\' . $fileName;
    }

    /**
     * 扫描 app/ 目录中的 Attribute 路由
     */
    private function scanAttributeRoutes(): void
    {
        $scanDirs = config('app.scan_dirs', []);
        
        foreach ($scanDirs as $dir) {
            $path = $this->basePath('/' . $dir);
            if (!is_dir($path)) continue;

            foreach (glob($path . '/*.php') as $file) {
                $this->scanFileForRoutes($file);
            }
        }
    }

    /**
     * 扫描单个文件中的路由 Attribute
     */
    private function scanFileForRoutes(string $file): void
    {
        $contents = file_get_contents($file);
        
        // 简单的 Attribute 扫描（生产环境应使用反射）
        if (preg_match_all('/#\\[Route\\(([^\\]]+)\\)\\]/', $contents, $matches)) {
            // 路由已注册到 Router（由 LifecycleManager 处理）
        }
    }

    public function getModuleManager(): ?ModuleManager
    {
        return $this->moduleManager;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
