<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Framework\Cache\CacheManager;

class CacheTest extends TestCase
{
    public function test_cache_can_store_and_retrieve_values()
    {
        $cache = $this->app->make(CacheManager::class);
        $cache->set('test_key', 'test_value', 60);
        
        $this->assertTrue($cache->has('test_key'));
        $this->assertEquals('test_value', $cache->get('test_key'));
    }

    public function test_cache_remember_logic()
    {
        $cache = $this->app->make(CacheManager::class);
        $cache->delete('remember_key');

        $value = $cache->remember('remember_key', function() {
            return 'computed_value';
        });

        $this->assertEquals('computed_value', $value);
        $this->assertEquals('computed_value', $cache->get('remember_key'));
    }

    public function test_different_stores()
    {
        $cache = $this->app->make(CacheManager::class);
        
        // 测试内存驱动（如果配置中有）
        $memory = $cache->store('memory');
        $memory->set('mem_key', 'mem_val');
        
        $this->assertEquals('mem_val', $memory->get('mem_key'));
        $this->assertFalse($cache->store('file')->has('mem_key'));
    }
}
