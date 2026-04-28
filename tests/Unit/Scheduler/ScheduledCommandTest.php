<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use Framework\Scheduler\ScheduledCommand;

class ScheduledCommandTest extends \PHPUnit\Framework\TestCase
{
    public function test_every_minute(): void
    {
        $executed = false;
        $cmd = new ScheduledCommand(fn() => $executed = true);
        $cmd->everyMinute();
        $this->assertTrue($cmd->isDue());
    }

    public function test_hourly(): void
    {
        $executed = false;
        $cmd = new ScheduledCommand(fn() => $executed = true);
        $cmd->hourly();
        $minute = (int) date('i');
        
        // hourly means minute = 0
        if ($minute === 0) {
            $this->assertTrue($cmd->isDue());
        } else {
            $this->assertFalse($cmd->isDue());
        }
    }

    public function test_daily(): void
    {
        $executed = false;
        $cmd = new ScheduledCommand(fn() => $executed = true);
        $cmd->daily();
        $hour = (int) date('H');
        $minute = (int) date('i');
        
        if ($hour === 0 && $minute === 0) {
            $this->assertTrue($cmd->isDue());
        } else {
            $this->assertFalse($cmd->isDue());
        }
    }

    public function test_custom_cron(): void
    {
        $executed = false;
        $cmd = new ScheduledCommand(fn() => $executed = true);
        $cmd->cron('*/5 * * * *');
        $minute = (int) date('i');
        
        // */5 means every 5 minutes (0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55)
        if ($minute % 5 === 0) {
            $this->assertTrue($cmd->isDue());
        } else {
            $this->assertFalse($cmd->isDue());
        }
        
        // Test a cron that always matches
        $cmd2 = new ScheduledCommand(fn() => true);
        $cmd2->cron('* * * * *');
        $this->assertTrue($cmd2->isDue());
    }

    public function test_when_condition_true(): void
    {
        $executed = false;
        $cmd = new ScheduledCommand(fn() => $executed = true);
        $cmd->everyMinute()->when(fn() => true);
        $this->assertTrue($cmd->isDue());
    }

    public function test_when_condition_false(): void
    {
        $executed = false;
        $cmd = new ScheduledCommand(fn() => $executed = true);
        $cmd->everyMinute()->when(fn() => false);
        $this->assertFalse($cmd->isDue());
    }

    public function test_cron_expression_match(): void
    {
        $cmd = new ScheduledCommand(fn() => true);
        $cmd->cron('* * * * *');
        $this->assertTrue($cmd->isDue());
    }

    public function test_invalid_cron_expression(): void
    {
        $cmd = new ScheduledCommand(fn() => true);
        $cmd->cron('invalid');
        $this->assertFalse($cmd->isDue());
    }
}
