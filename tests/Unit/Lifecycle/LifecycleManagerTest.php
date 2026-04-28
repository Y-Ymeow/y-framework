<?php

declare(strict_types=1);

namespace Tests\Unit\Lifecycle;

use Framework\Lifecycle\LifecycleManager;
use Framework\Lifecycle\RouteCollector;
use Framework\Lifecycle\ComponentCollector;
use Framework\Lifecycle\ServiceCollector;
use Framework\Events\Hook;

class LifecycleManagerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        LifecycleManager::reset();
    }

    public function test_get_instance(): void
    {
        $manager = LifecycleManager::getInstance();
        $this->assertInstanceOf(LifecycleManager::class, $manager);
        $this->assertSame($manager, LifecycleManager::getInstance());
    }

    public function test_register_collector(): void
    {
        $manager = LifecycleManager::getInstance();
        $collector = new RouteCollector();
        $manager->registerCollector('routes', $collector);

        $this->assertSame($collector, $manager->getCollector('routes'));
    }

    public function test_register_route(): void
    {
        $manager = LifecycleManager::getInstance();
        $manager->registerCollector('routes', new RouteCollector());

        $manager->registerRoute([
            'method' => 'GET',
            'path' => '/test',
            'handler' => 'TestController@test',
        ]);

        $this->assertEquals(1, $manager->getCollector('routes')->count());
    }

    public function test_register_component(): void
    {
        $manager = LifecycleManager::getInstance();
        $manager->registerCollector('components', new ComponentCollector());

        $manager->registerComponent([
            'class' => 'App\Components\Button',
            'name' => 'button',
        ]);

        $this->assertEquals(1, $manager->getCollector('components')->count());
    }

    public function test_register_service(): void
    {
        $manager = LifecycleManager::getInstance();
        $manager->registerCollector('services', new ServiceCollector());

        $manager->registerService('cache', 'App\Services\CacheService', true);

        $this->assertEquals(1, $manager->getCollector('services')->count());
        $this->assertCount(1, $manager->getCollector('services')->getSingletons());
    }

    public function test_pending_registrations(): void
    {
        $manager = LifecycleManager::getInstance();

        $manager->registerRoute([
            'method' => 'GET',
            'path' => '/test',
            'handler' => 'TestController@test',
        ]);

        $manager->registerCollector('routes', new RouteCollector());
        $manager->flushPending();

        $this->assertEquals(1, $manager->getCollector('routes')->count());
    }

    public function test_boot(): void
    {
        $manager = LifecycleManager::getInstance();
        $this->assertFalse($manager->isBooted());

        $manager->boot();
        $this->assertTrue($manager->isBooted());

        $manager->boot();
        $this->assertTrue($manager->isBooted());
    }

    public function test_hook_integration(): void
    {
        $manager = LifecycleManager::getInstance();
        $executed = false;

        $manager->bindHook('test.event', function () use (&$executed) {
            $executed = true;
        });

        $manager->fire('test.event');
        $this->assertTrue($executed);
    }

    public function test_filter_integration(): void
    {
        $manager = LifecycleManager::getInstance();

        $manager->addFilter('test.filter', function ($value) {
            return strtoupper($value);
        });

        $result = $manager->filter('test.filter', 'hello');
        $this->assertEquals('HELLO', $result);
    }

    public function test_boot_triggers_hooks(): void
    {
        $manager = LifecycleManager::getInstance();
        $bootingCalled = false;
        $bootedCalled = false;

        $manager->bindHook('app.booting', function () use (&$bootingCalled) {
            $bootingCalled = true;
        });

        $manager->bindHook('app.booted', function () use (&$bootedCalled) {
            $bootedCalled = true;
        });

        $manager->boot();

        $this->assertTrue($bootingCalled);
        $this->assertTrue($bootedCalled);
    }

    public function test_get_summary(): void
    {
        $manager = LifecycleManager::getInstance();
        $manager->registerCollector('routes', new RouteCollector());
        $manager->registerCollector('services', new ServiceCollector());

        $manager->registerRoute(['method' => 'GET', 'path' => '/test', 'handler' => 'Test@index']);
        $manager->registerService('cache', 'App\Services\CacheService', true);

        $summary = $manager->getSummary();

        $this->assertEquals(1, $summary['routes']['count']);
        $this->assertEquals(1, $summary['services']['count']);
    }

    public function test_global_hooks(): void
    {
        $manager = LifecycleManager::getInstance();
        $hooks = $manager->getGlobalHooks();

        $this->assertContains('app.booted', $hooks);
        $this->assertContains('routes.registered', $hooks);
        $this->assertContains('services.registered', $hooks);
    }

    public function test_reset(): void
    {
        $manager = LifecycleManager::getInstance();
        $manager->boot();

        LifecycleManager::reset();

        $manager = LifecycleManager::getInstance();
        $this->assertFalse($manager->isBooted());
    }
}
