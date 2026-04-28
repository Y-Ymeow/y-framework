<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Framework\Events\Hook;

class HookTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Hook::reset();
    }

    public function test_add_action_and_fire(): void
    {
        $executed = false;
        Hook::addAction('test.event', function () use (&$executed) {
            $executed = true;
        });

        Hook::fire('test.event');
        $this->assertTrue($executed);
    }

    public function test_fire_with_arguments(): void
    {
        $received = null;
        Hook::addAction('test.event', function ($arg1, $arg2) use (&$received) {
            $received = [$arg1, $arg2];
        }, 10, 2);

        Hook::fire('test.event', 'hello', 'world');
        $this->assertEquals(['hello', 'world'], $received);
    }

    public function test_filter_modifies_value(): void
    {
        Hook::addFilter('test.filter', function ($value) {
            return strtoupper($value);
        });

        $result = Hook::filter('test.filter', 'hello');
        $this->assertEquals('HELLO', $result);
    }

    public function test_filter_chain(): void
    {
        Hook::addFilter('test.filter', function ($value) {
            return $value . '1';
        }, 10);

        Hook::addFilter('test.filter', function ($value) {
            return $value . '2';
        }, 20);

        $result = Hook::filter('test.filter', 'start');
        $this->assertEquals('start12', $result);
    }

    public function test_priority_order(): void
    {
        $order = [];
        Hook::addAction('test.event', function () use (&$order) {
            $order[] = 'low';
        }, 20);

        Hook::addAction('test.event', function () use (&$order) {
            $order[] = 'high';
        }, 5);

        Hook::fire('test.event');
        $this->assertEquals(['high', 'low'], $order);
    }

    public function test_has_action(): void
    {
        Hook::addAction('test.event', fn() => null);
        $this->assertTrue(Hook::hasAction('test.event'));
        $this->assertFalse(Hook::hasAction('nonexistent.event'));
    }

    public function test_has_filter(): void
    {
        Hook::addFilter('test.filter', fn($v) => $v);
        $this->assertTrue(Hook::hasFilter('test.filter'));
        $this->assertFalse(Hook::hasFilter('nonexistent.filter'));
    }

    public function test_fired(): void
    {
        Hook::fire('test.event');
        $this->assertTrue(Hook::fired('test.event'));
        $this->assertFalse(Hook::fired('other.event'));
    }

    public function test_remove_action(): void
    {
        $callback = fn() => null;
        Hook::addAction('test.event', $callback);
        Hook::removeAction('test.event', $callback);
        $this->assertFalse(Hook::hasAction('test.event'));
    }

    public function test_clear(): void
    {
        Hook::addAction('test.event', fn() => null);
        Hook::addFilter('test.event', fn($v) => $v);
        Hook::clear('test.event');

        $this->assertFalse(Hook::hasAction('test.event'));
        $this->assertFalse(Hook::hasFilter('test.event'));
        $this->assertFalse(Hook::fired('test.event'));
    }

    public function test_bind(): void
    {
        $executed = false;
        Hook::bind('test.event', function () use (&$executed) {
            $executed = true;
        });

        Hook::fire('test.event');
        $this->assertTrue($executed);
    }

    public function test_multiple_listeners(): void
    {
        $count = 0;
        Hook::addAction('test.event', function () use (&$count) { $count++; });
        Hook::addAction('test.event', function () use (&$count) { $count++; });
        Hook::addAction('test.event', function () use (&$count) { $count++; });

        Hook::fire('test.event');
        $this->assertEquals(3, $count);
    }

    public function test_accepted_args_limit(): void
    {
        $received = [];
        Hook::addAction('test.event', function () use (&$received) {
            $received = func_get_args();
        }, 10, 2);

        Hook::fire('test.event', 'a', 'b', 'c', 'd');
        $this->assertEquals(['a', 'b'], $received);
    }

    public function test_get_all_actions(): void
    {
        Hook::addAction('event1', fn() => null);
        Hook::addAction('event2', fn() => null);
        $actions = Hook::getAllActions();
        $this->assertArrayHasKey('event1', $actions);
        $this->assertArrayHasKey('event2', $actions);
    }

    public function test_get_all_filters(): void
    {
        Hook::addFilter('filter1', fn($v) => $v);
        Hook::addFilter('filter2', fn($v) => $v);
        $filters = Hook::getAllFilters();
        $this->assertArrayHasKey('filter1', $filters);
        $this->assertArrayHasKey('filter2', $filters);
    }

    public function test_reset(): void
    {
        Hook::addAction('test.event', fn() => null);
        Hook::fire('test.event');
        Hook::reset();

        $this->assertFalse(Hook::hasAction('test.event'));
        $this->assertFalse(Hook::fired('test.event'));
    }
}
