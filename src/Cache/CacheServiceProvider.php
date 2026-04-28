<?php

declare(strict_types=1);

namespace Framework\Cache;

use Framework\Foundation\ServiceProvider;
use Psr\SimpleCache\CacheInterface;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheManager::class, function () {
            $app = \Framework\Foundation\Application::getInstance();
            $config = include $app->configPath('cache.php');
            return new CacheManager($config);
        });

        $this->app->alias(CacheManager::class, CacheInterface::class);
        $this->app->alias(CacheManager::class, 'cache');
    }
}
