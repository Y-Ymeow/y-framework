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
        $kernel = require base_path('routes/schedule.php');
        if (is_callable($kernel)) {
            $kernel(app()->make(Scheduler::class));
        }

        $router = app()->make(Router::class);
        if ($router) {
            $router->post('/_schedule/run', [new SchedulerRoute(), 'run']);
        }
    }
}
