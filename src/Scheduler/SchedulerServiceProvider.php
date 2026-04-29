<?php

declare(strict_types=1);

namespace Framework\Scheduler;

use Framework\Foundation\ServiceProvider;
use Framework\Routing\Router;

class SchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton(Scheduler::class, fn() => new Scheduler());
        
        if (!function_exists('schedule')) {
            function schedule(callable $callback): ScheduledCommand
            {
                return app()->make(Scheduler::class)->call($callback);
            }
        }
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

        // 路由会自动通过属性扫描注册，不需要在这里手动添加
    }
}
