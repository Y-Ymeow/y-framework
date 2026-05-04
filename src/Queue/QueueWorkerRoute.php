<?php

declare(strict_types=1);

namespace Framework\Queue;

use Framework\Routing\Attribute\Post;
use Framework\Routing\Attribute\Route;
use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup('/_queue')]
class QueueWorkerRoute
{
    #[Route('/worker', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $token = $request->header('x-queue-token') ?? $request->input('token', '');
        $configToken = config('queue.route.token', 'change-me');

        if ($token !== $configToken || $configToken === 'change-me') {
            return Response::json(['success' => false, 'error' => 'Invalid token'], 403);
        }

        $queue = $request->input('queue', 'default');
        $timeout = (int) $request->input('timeout', 30);

        $job = QueueManager::driver()->pop($queue);
        
        if ($job === null) {
            return Response::json(['success' => true, 'job' => null]);
        }

        try {
            set_time_limit($timeout);
            $job->handle();
            return Response::json(['success' => true, 'job' => $job->id]);
        } catch (\Throwable $e) {
            $job->failed($e);
            return Response::json([
                'success' => false, 
                'job' => $job->id, 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
