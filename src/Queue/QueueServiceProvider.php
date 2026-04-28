<?php

declare(strict_types=1);

namespace Framework\Queue;

use Framework\Foundation\Application;
use Framework\Foundation\ServiceProvider;
use Framework\Routing\Router;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = config('queue', []);
        QueueManager::init($config);

        if (!function_exists('queue')) {
            function queue(string|callable $job, array $data = [], ?string $queue = null, int $delay = 0): bool
            {
                return QueueManager::push($job, $data, $queue, $delay);
            }
        }
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        if ($router) {
            $router->post('/_queue/worker', [new QueueWorkerRoute(), 'handle']);
        }
    }
}
