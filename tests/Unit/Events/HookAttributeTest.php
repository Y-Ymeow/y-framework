<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Framework\Events\Hook;
use Framework\Events\Attribute\HookListener;
use Framework\Events\Attribute\HookFilter;
use Framework\Lifecycle\LifecycleManager;

class HookAttributeTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Hook::reset();
        LifecycleManager::reset();
    }

    public function test_hook_listener_attribute(): void
    {
        $listener = new HookListener('test.event', 10, 2);
        $this->assertEquals('test.event', $listener->hook);
        $this->assertEquals(10, $listener->priority);
        $this->assertEquals(2, $listener->acceptedArgs);
    }

    public function test_hook_filter_attribute(): void
    {
        $filter = new HookFilter('test.filter', 20);
        $this->assertEquals('test.filter', $filter->hook);
        $this->assertEquals(20, $filter->priority);
    }

    public function test_scan_attributes(): void
    {
        $manager = LifecycleManager::getInstance();
        
        $tempDir = sys_get_temp_dir() . '/hook_test_' . uniqid();
        mkdir($tempDir);

        $phpFile = <<<'PHP'
<?php
namespace TestHookClasses;

use Framework\Events\Attribute\HookListener;
use Framework\Events\Attribute\HookFilter;

class TestListener
{
    #[HookListener('app.booted')]
    public function onBooted(): void
    {
    }

    #[HookFilter('test.filter')]
    public function filterValue(string $value): string
    {
        return strtoupper($value);
    }
}
PHP;

        file_put_contents($tempDir . '/TestListener.php', $phpFile);

        $manager->scanAttributes($tempDir);

        $this->assertTrue(Hook::hasAction('app.booted'));
        $this->assertTrue(Hook::hasFilter('test.filter'));

        $result = Hook::filter('test.filter', 'hello');
        $this->assertEquals('HELLO', $result);

        unlink($tempDir . '/TestListener.php');
        rmdir($tempDir);
    }

    public function test_multiple_hooks_on_same_method(): void
    {
        $tempDir = sys_get_temp_dir() . '/hook_test_' . uniqid();
        mkdir($tempDir);

        $phpFile = <<<'PHP'
<?php
namespace TestMultiHook;

use Framework\Events\Attribute\HookListener;

class MultiListener
{
    #[HookListener('event.one')]
    #[HookListener('event.two')]
    public function onMultiple(): void
    {
    }
}
PHP;

        file_put_contents($tempDir . '/MultiListener.php', $phpFile);

        $manager = LifecycleManager::getInstance();
        $manager->scanAttributes($tempDir);

        $this->assertTrue(Hook::hasAction('event.one'));
        $this->assertTrue(Hook::hasAction('event.two'));

        unlink($tempDir . '/MultiListener.php');
        rmdir($tempDir);
    }

    public function test_priority_order(): void
    {
        $tempDir = sys_get_temp_dir() . '/hook_test_' . uniqid();
        mkdir($tempDir);

        $phpFile1 = <<<'PHP'
<?php
namespace TestPriority;

use Framework\Events\Attribute\HookListener;

class LowPriority
{
    #[HookListener('test.event', priority: 20)]
    public function low(): void
    {
    }
}
PHP;

        $phpFile2 = <<<'PHP'
<?php
namespace TestPriority;

use Framework\Events\Attribute\HookListener;

class HighPriority
{
    #[HookListener('test.event', priority: 5)]
    public function high(): void
    {
    }
}
PHP;

        file_put_contents($tempDir . '/LowPriority.php', $phpFile1);
        file_put_contents($tempDir . '/HighPriority.php', $phpFile2);

        $manager = LifecycleManager::getInstance();
        $manager->scanAttributes($tempDir);

        $actions = Hook::getAllActions();
        $this->assertArrayHasKey('test.event', $actions);
        
        $priorities = array_keys($actions['test.event']);
        $this->assertEquals([5, 20], $priorities);

        unlink($tempDir . '/LowPriority.php');
        unlink($tempDir . '/HighPriority.php');
        rmdir($tempDir);
    }
}
