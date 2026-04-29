<?php

declare(strict_types=1);

namespace Framework\Queue;

use Framework\Foundation\Application;
use Framework\Foundation\ServiceProvider;
use Framework\Routing\Router;

if (!function_exists(__NAMESPACE__ . '\queue')) {
    function queue(string|callable $job, array $data = [], ?string $queue = null, int $delay = 0): bool
    {
        return QueueManager::push($job, $data, $queue, $delay);
    }
}

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = config('queue', []);
        QueueManager::init($config);
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        if ($router) {
            $router->post('/_queue/worker', [new QueueWorkerRoute(), 'handle']);
        }
    }
}
