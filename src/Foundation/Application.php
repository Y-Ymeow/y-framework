<?php

declare(strict_types=1);

namespace Framework\Foundation;

use Framework\Http\Request\Request;
use Framework\Support\Paths;

class Application
{
    private string $basePath;
    private Paths $paths;
    private Container $container;
    private bool $booted = false;
    private array $providers = [];
    private static ?Application $instance = null;

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
        $providers = config('app.providers', []);
        foreach ($providers as $providerClass) {
            $this->register(new $providerClass($this));
        }

        if (config('app.debug', false)) {
            $debugProviders = config('app.debug_providers', []);
            foreach ($debugProviders as $providerClass) {
                $this->register(new $providerClass($this));
            }
        }

        $this->make(\Framework\Intl\IntlServiceProvider::class)->register();

        $this->boot();
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
