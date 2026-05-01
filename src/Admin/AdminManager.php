<?php

namespace Framework\Admin;

use Framework\Admin\Resource\ResourceInterface;
use Framework\Admin\Page\PageInterface;
use Framework\Component\Live\LiveComponent;

class AdminManager
{
    protected static array $resources = [];
    protected static array $pages = [];
    protected static ?string $prefix = '/admin';
    protected static ?string $brandTitle = 'Admin';
    protected static bool $booted = false;

    public static function registerResource(string $resource): void
    {
        if (!is_subclass_of($resource, ResourceInterface::class)) {
            throw new \InvalidArgumentException("{$resource} must implement " . ResourceInterface::class);
        }
        static::$resources[$resource::getName()] = $resource;
    }

    public static function registerPage(string $page): void
    {
        if (!is_subclass_of($page, PageInterface::class) && !is_subclass_of($page, LiveComponent::class)) {
            throw new \InvalidArgumentException("{$page} must implement " . PageInterface::class . " or extend " . LiveComponent::class);
        }
        static::$pages[$page::getName()] = $page;
    }

    public static function getResources(): array
    {
        return array_values(static::$resources);
    }

    public static function getPages(): array
    {
        return array_values(static::$pages);
    }

    public static function getResource(string $name): ?string
    {
        return static::$resources[$name] ?? null;
    }

    public static function getPage(string $name): ?string
    {
        return static::$pages[$name] ?? null;
    }

    public static function setPrefix(string $prefix): void
    {
        static::$prefix = $prefix;
    }

    public static function getPrefix(): string
    {
        return static::$prefix;
    }

    /**
     * 从配置加载 admin 页面
     */
    public static function bootFromConfig(): void
    {
        if (static::$booted) {
            return;
        }

        $pages = config('admin.pages', []);

        foreach ($pages as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }
            static::$pages[$name] = $class;
        }

        static::$booted = true;
    }

    public static function registerRoutes(\Framework\Routing\Router $router): void
    {
        static::bootFromConfig();

        $prefix = static::getPrefix();

        // 注册仪表盘路由
        $dashboardClass = static::$pages['dashboard'] ?? \Framework\Admin\Pages\DashboardPage::class;
        $router->addRoute('GET', $prefix, [$dashboardClass, 'render'], 'admin.dashboard');

        // 注册登录页面路由（不需要 layout）
        if (isset(static::$pages['login'])) {
            $loginClass = static::$pages['login'];
            $router->addRoute('GET', $prefix . '/login', [$loginClass, 'render'], 'admin.login');
        }

        foreach (static::$resources as $resourceClass) {
            $routes = $resourceClass::getRoutes();
            foreach ($routes as $name => $config) {
                $method = $config['method'] ?? 'GET';
                $path = $prefix . $config['path'];
                $handler = $config['handler'];

                $router->addRoute($method, $path, $handler, $name);
            }
        }

        foreach (static::$pages as $name => $pageClass) {
            if (in_array($name, ['dashboard', 'login'], true)) {
                continue;
            }

            if (method_exists($pageClass, 'getRoutes')) {
                $routes = $pageClass::getRoutes();
                foreach ($routes as $routeName => $config) {
                    $method = $config['method'] ?? 'GET';
                    $path = $prefix . $config['path'];
                    $handler = $config['handler'];

                    $router->addRoute($method, $path, $handler, $routeName);
                }
            } else {
                $router->addRoute('GET', $prefix . '/' . $name, [$pageClass, 'render'], 'admin.page.' . $name);
            }
        }
    }

    public static function brand(string $title): void
    {
        static::$brandTitle = $title;
    }

    public static function getBrandTitle(): string
    {
        return static::$brandTitle;
    }
}
