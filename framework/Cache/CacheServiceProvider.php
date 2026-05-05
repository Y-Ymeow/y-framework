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
            return new CacheManager(config('cache'));
        });

        $this->app->alias(CacheManager::class, CacheInterface::class);
        $this->app->alias(CacheManager::class, 'cache');
    }
}
