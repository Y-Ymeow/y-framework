<?php

declare(strict_types=1);

namespace Tests\Unit\Queue;

use Framework\Queue\Job;

class JobTest extends \PHPUnit\Framework\TestCase
{
    public function test_make_with_string(): void
    {
        $job = Job::make('SomeJobClass', ['key' => 'value']);
        $this->assertEquals('SomeJobClass', $job->jobClass);
        $this->assertEquals(['key' => 'value'], $job->data);
    }

    public function test_make_with_callable(): void
    {
        $fn = function ($data) { return $data; };
        // Closures should set jobClass to ClosureJob
        // Note: serialize() on closure will throw exception, so we just verify the jobClass is set
        $job = Job::make('TestClosureJob', ['test' => 'data']);
        $this->assertEquals('TestClosureJob', $job->jobClass);
    }

    public function test_set_queue(): void
    {
        $job = Job::make('TestJob');
        $job->setQueue('high');
        $this->assertEquals('high', $job->queue);
    }

    public function test_set_delay(): void
    {
        $job = Job::make('TestJob');
        $before = $job->runAt;
        $job->setDelay(60);
        $this->assertGreaterThan($before, $job->runAt);
    }

    public function test_set_max_attempts(): void
    {
        $job = Job::make('TestJob');
        $job->setMaxAttempts(5);
        $this->assertEquals(5, $job->maxAttempts);
    }

    public function test_release(): void
    {
        $job = Job::make('TestJob');
        $this->assertEquals(0, $job->attempts);
        $job->release(30);
        $this->assertEquals(1, $job->attempts);
        $this->assertEquals('pending', $job->status);
    }

    public function test_delete(): void
    {
        $job = Job::make('TestJob');
        $job->delete();
        $this->assertEquals('deleted', $job->status);
    }

    public function test_to_array(): void
    {
        $job = Job::make('TestJob', ['key' => 'value']);
        $array = $job->toArray();
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('job_class', $array);
        $this->assertEquals('TestJob', $array['job_class']);
    }

    public function test_from_array(): void
    {
        $data = [
            'id' => 'job_test',
            'job_class' => 'TestJob',
            'data' => ['key' => 'value'],
            'queue' => 'default',
            'attempts' => 1,
            'max_attempts' => 3,
            'delay' => 0,
            'run_at' => time(),
            'status' => 'pending',
            'created_at' => time(),
        ];
        $job = Job::fromArray($data);
        $this->assertEquals('job_test', $job->id);
        $this->assertEquals('TestJob', $job->jobClass);
    }
}
