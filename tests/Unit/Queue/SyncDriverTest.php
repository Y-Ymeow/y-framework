<?php

declare(strict_types=1);

namespace Tests\Unit\Queue;

use Framework\Queue\Job;
use Framework\Queue\SyncDriver;

class SyncDriverTest extends \PHPUnit\Framework\TestCase
{
    public function test_push_returns_true(): void
    {
        $driver = new SyncDriver();
        // Use a string job class that exists for testing
        $job = Job::make('Framework\Queue\TestJob');
        $this->assertTrue($driver->push($job));
    }

    public function test_pop_returns_null(): void
    {
        $driver = new SyncDriver();
        $this->assertNull($driver->pop());
    }

    public function test_size_returns_zero(): void
    {
        $driver = new SyncDriver();
        $this->assertEquals(0, $driver->size());
    }

    public function test_clear_returns_true(): void
    {
        $driver = new SyncDriver();
        $this->assertTrue($driver->clear());
    }
}
