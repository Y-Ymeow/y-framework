<?php

namespace Framework\Admin;

use Framework\Admin\Resource\ResourceInterface;
use Framework\Admin\Page\PageInterface;

class AdminManager
{
    protected static array $resources = [];
    protected static array $pages = [];
    protected static ?string $prefix = '/admin';
    protected static ?string $brandTitle = 'Admin';

    public static function registerResource(string $resource): void
    {
        if (!is_subclass_of($resource, ResourceInterface::class)) {
            throw new \InvalidArgumentException("{$resource} must implement " . ResourceInterface::class);
        }
        static::$resources[$resource::getName()] = $resource;
    }

    public static function registerPage(string $page): void
    {
        if (!is_subclass_of($page, PageInterface::class)) {
            throw new \InvalidArgumentException("{$page} must implement " . PageInterface::class);
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

    public static function registerRoutes(\Framework\Routing\Router $router): void
    {
        $prefix = static::getPrefix();

        // 注册后台仪表盘路由
        $router->addRoute('GET', $prefix, [AdminResourceController::class, 'dashboard'], 'admin.dashboard');

        foreach (static::$resources as $resourceClass) {
            $routes = $resourceClass::getRoutes();
            foreach ($routes as $name => $config) {
                $method = $config['method'] ?? 'GET';
                $path = $prefix . $config['path'];
                $handler = $config['handler'];
                
                $router->addRoute($method, $path, $handler, $name);
            }
        }

        foreach (static::$pages as $pageClass) {
            $routes = $pageClass::getRoutes();
            foreach ($routes as $name => $config) {
                $method = $config['method'] ?? 'GET';
                $path = $prefix . $config['path'];
                $handler = $config['handler'];

                $router->addRoute($method, $path, $handler, $name);
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