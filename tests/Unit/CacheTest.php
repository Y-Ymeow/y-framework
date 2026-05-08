<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Framework\Cache\CacheManager;
use Framework\Cache\Contracts\StoreInterface;
use Framework\Cache\Drivers\ArrayDriver;
use Framework\Cache\Exception\InvalidArgumentException;
use Framework\Cache\Lock\ArrayLock;

class CacheTest extends TestCase
{
    private CacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->app->make(CacheManager::class);
    }

    public function test_cache_can_store_and_retrieve_values()
    {
        $this->cache->set('test_key', 'test_value', 60);

        $this->assertTrue($this->cache->has('test_key'));
        $this->assertEquals('test_value', $this->cache->get('test_key'));
    }

    public function test_cache_returns_default_for_missing_key()
    {
        $this->assertNull($this->cache->get('nonexistent'));
        $this->assertEquals('fallback', $this->cache->get('nonexistent', 'fallback'));
    }

    public function test_cache_remember_logic()
    {
        $this->cache->delete('remember_key');

        $value = $this->cache->remember('remember_key', function () {
            return 'computed_value';
        });

        $this->assertEquals('computed_value', $value);
        $this->assertEquals('computed_value', $this->cache->get('remember_key'));
    }

    public function test_cache_remember_forever()
    {
        $this->cache->delete('forever_key');

        $value = $this->cache->rememberForever('forever_key', function () {
            return 'forever_value';
        });

        $this->assertEquals('forever_value', $value);
        $this->assertEquals('forever_value', $this->cache->get('forever_key'));
    }

    public function test_cache_pull()
    {
        $this->cache->set('pull_key', 'pull_value', 60);

        $value = $this->cache->pull('pull_key');
        $this->assertEquals('pull_value', $value);
        $this->assertFalse($this->cache->has('pull_key'));
    }

    public function test_cache_put_many()
    {
        $this->cache->putMany([
            'key1' => 'val1',
            'key2' => 'val2',
        ], 60);

        $this->assertEquals('val1', $this->cache->get('key1'));
        $this->assertEquals('val2', $this->cache->get('key2'));
    }

    public function test_cache_delete()
    {
        $this->cache->set('del_key', 'del_value', 60);
        $this->assertTrue($this->cache->has('del_key'));

        $this->cache->delete('del_key');
        $this->assertFalse($this->cache->has('del_key'));
    }

    public function test_cache_clear()
    {
        $this->cache->set('clear_key1', 'val1', 60);
        $this->cache->set('clear_key2', 'val2', 60);

        $this->cache->clear();
        $this->assertFalse($this->cache->has('clear_key1'));
        $this->assertFalse($this->cache->has('clear_key2'));
    }

    public function test_different_stores()
    {
        $memory = $this->cache->store('array');
        $memory->set('mem_key', 'mem_val');

        $this->assertEquals('mem_val', $memory->get('mem_key'));
        $this->assertFalse($this->cache->store('file')->has('mem_key'));
    }

    public function test_increment_and_decrement()
    {
        $store = $this->cache->store('array');
        $store->set('counter', 10, 60);

        $this->assertEquals(12, $store->increment('counter', 2));
        $this->assertEquals(9, $store->decrement('counter', 3));
    }

    public function test_increment_from_zero()
    {
        $store = $this->cache->store('array');
        $store->delete('new_counter');

        $this->assertEquals(1, $store->increment('new_counter'));
    }

    public function test_lock_acquire_and_release()
    {
        $store = $this->cache->store('array');
        $lock = $store->lock('test_lock', 10);

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->release());
    }

    public function test_lock_is_owned_by_current_process()
    {
        $store = $this->cache->store('array');
        $lock = $store->lock('owner_test', 10);

        $this->assertFalse($lock->isOwnedByCurrentProcess());
        $lock->acquire();
        $this->assertTrue($lock->isOwnedByCurrentProcess());
        $lock->release();
    }

    public function test_tags()
    {
        $store = $this->cache->store('array');
        $tagged = $this->cache->tags(['users', 'active']);

        $tagged->set('user_1', 'Alice', 60);
        $tagged->set('user_2', 'Bob', 60);

        $this->assertEquals('Alice', $tagged->get('user_1'));
        $this->assertEquals('Bob', $tagged->get('user_2'));

        $tagged->flush();

        $this->assertNull($tagged->get('user_1'));
        $this->assertNull($tagged->get('user_2'));
    }

    public function test_colon_in_key_works_by_default()
    {
        $store = $this->cache->store('array');
        $store->set('css_snippet:main', 'value', 60);
        $this->assertEquals('value', $store->get('css_snippet:main'));
    }

    public function test_empty_key_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get('');
    }

    public function test_get_multiple_and_set_multiple()
    {
        $store = $this->cache->store('array');

        $store->setMultiple([
            'multi1' => 'val1',
            'multi2' => 'val2',
        ], 60);

        $result = $store->getMultiple(['multi1', 'multi2', 'multi3'], 'default');
        $this->assertEquals('val1', $result['multi1']);
        $this->assertEquals('val2', $result['multi2']);
        $this->assertEquals('default', $result['multi3']);
    }

    public function test_delete_multiple()
    {
        $store = $this->cache->store('array');

        $store->set('dm1', 'val1', 60);
        $store->set('dm2', 'val2', 60);

        $store->deleteMultiple(['dm1', 'dm2']);

        $this->assertFalse($store->has('dm1'));
        $this->assertFalse($store->has('dm2'));
    }
}
