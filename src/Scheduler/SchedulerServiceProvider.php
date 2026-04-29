<?php

declare(strict_types=1);

namespace Framework\Scheduler;

use Framework\Foundation\ServiceProvider;
use Framework\Routing\Router;

if (!function_exists(__NAMESPACE__ . '\schedule')) {
    function schedule(callable $callback): ScheduledCommand
    {
        return app()->make(Scheduler::class)->call($callback);
    }
}

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton(Scheduler::class, fn() => new Scheduler());
    }

    public function boot(): void
    {
        $schedulePath = base_path('routes/schedule.php');
        if (file_exists($schedulePath)) {
            $kernel = require $schedulePath;
            if (is_callable($kernel)) {
                $kernel(app()->make(Scheduler::class));
            }
        }
    }
}
