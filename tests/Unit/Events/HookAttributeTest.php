<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Framework\Events\Hook;
use Framework\Events\Event;
use Framework\Events\Attribute\Listen;
use Framework\Events\Attribute\HookListener;
use Framework\Events\Attribute\HookFilter;
use Framework\Events\EventSubscriberInterface;
use Framework\Lifecycle\LifecycleManager;

class HookAttributeTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Hook::reset();
        LifecycleManager::reset();
    }

    public function test_listen_attribute(): void
    {
        $listen = new Listen('test.event', 10, 2);
        $this->assertEquals('test.event', $listen->event);
        $this->assertEquals(10, $listen->priority);
        $this->assertEquals(2, $listen->acceptedArgs);
    }

    public function test_hook_listener_backward_compat(): void
    {
        $listener = new HookListener('test.event', 10, 2);
        $this->assertEquals('test.event', $listener->event);
        $this->assertEquals(10, $listener->priority);
    }

    public function test_hook_filter_backward_compat(): void
    {
        $filter = new HookFilter('test.filter', 20);
        $this->assertEquals('test.filter', $filter->event);
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

use Framework\Events\Attribute\Listen;

class TestListener
{
    #[Listen('app.booted')]
    public function onBooted(): void
    {
    }

    #[Listen('test.filter')]
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

        $result = Hook::applyFilter('test.filter', 'hello');
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

use Framework\Events\Attribute\Listen;

class MultiListener
{
    #[Listen('event.one')]
    #[Listen('event.two')]
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

use Framework\Events\Attribute\Listen;

class LowPriority
{
    #[Listen('test.event', priority: 20)]
    public function low(): void
    {
    }
}
PHP;

        $phpFile2 = <<<'PHP'
<?php
namespace TestPriority;

use Framework\Events\Attribute\Listen;

class HighPriority
{
    #[Listen('test.event', priority: 5)]
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

    public function test_dispatch_event_object(): void
    {
        $received = null;
        Hook::getInstance()->on('test.event', function (Event $event) use (&$received) {
            $received = $event;
        });

        $event = new Event('test.event', ['key' => 'value']);
        $result = Hook::getInstance()->dispatch($event);

        $this->assertSame($event, $result);
        $this->assertEquals('test.event', $received->getName());
        $this->assertEquals('value', $received->get('key'));
    }

    public function test_event_propagation(): void
    {
        $called = [];
        Hook::getInstance()->on('test.event', function (Event $event) use (&$called) {
            $called[] = 'first';
            $event->stopPropagation();
        }, 0);
        Hook::getInstance()->on('test.event', function (Event $event) use (&$called) {
            $called[] = 'second';
        }, 1);

        $event = new Event('test.event');
        Hook::getInstance()->dispatch($event);

        $this->assertEquals(['first'], $called);
    }

    public function test_wildcard_listeners(): void
    {
        $called = [];
        Hook::getInstance()->on('request.*', function () use (&$called) {
            $called[] = 'wildcard';
        });

        Hook::fire('request.received');
        Hook::fire('request.processed');
        Hook::fire('response.sent');

        $this->assertEquals(['wildcard', 'wildcard'], $called);
    }

    public function test_event_subscriber(): void
    {
        $subscriber = new class implements EventSubscriberInterface {
            public static array $called = [];

            public static function getSubscribedEvents(): array
            {
                return [
                    'app.booted' => 'onBooted',
                    'request.received' => ['onRequest', 10],
                ];
            }

            public function onBooted(): void
            {
                self::$called[] = 'booted';
            }

            public function onRequest(): void
            {
                self::$called[] = 'request';
            }
        };

        Hook::getInstance()->addSubscriber($subscriber);

        Hook::fire('app.booted');
        Hook::fire('request.received');

        $this->assertEquals(['booted', 'request'], $subscriber::$called);
    }

    public function test_emit_vs_dispatch(): void
    {
        $emitResult = null;
        $dispatchResult = null;

        Hook::getInstance()->on('test.emit', function () use (&$emitResult) {
            $emitResult = 'emitted';
        });
        Hook::getInstance()->on('test.dispatch', function (Event $event) use (&$dispatchResult) {
            $dispatchResult = $event->getName();
        });

        Hook::getInstance()->emit('test.emit');
        Hook::getInstance()->dispatch(new Event('test.dispatch'));

        $this->assertEquals('emitted', $emitResult);
        $this->assertEquals('test.dispatch', $dispatchResult);
    }

    public function test_filter_chain(): void
    {
        Hook::getInstance()->on('transform.value', function (string $value): string {
            return strtoupper($value);
        }, 0);
        Hook::getInstance()->on('transform.value', function (string $value): string {
            return $value . '!';
        }, 1);

        $result = Hook::getInstance()->filter('transform.value', 'hello');
        $this->assertEquals('HELLO!', $result);
    }
}
