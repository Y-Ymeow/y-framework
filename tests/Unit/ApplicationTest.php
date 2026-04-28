<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Framework\Foundation\Application;
use Framework\Cache\CacheManager;
use Psr\SimpleCache\CacheInterface;

class ApplicationTest extends TestCase
{
    public function test_container_can_resolve_core_services()
    {
        $this->assertInstanceOf(Application::class, $this->app->make(Application::class));
        $this->assertInstanceOf(CacheManager::class, $this->app->make(CacheManager::class));
        $this->assertInstanceOf(CacheInterface::class, $this->app->make(CacheInterface::class));
    }

    public function test_container_singleton_binding()
    {
        $instance1 = $this->app->make(CacheManager::class);
        $instance2 = $this->app->make(CacheManager::class);
        
        $this->assertSame($instance1, $instance2);
    }

    public function test_container_alias()
    {
        $this->assertSame(
            $this->app->make(CacheManager::class),
            $this->app->make('cache')
        );
    }
}
