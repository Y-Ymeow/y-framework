<?php

declare(strict_types=1);

namespace Framework\Scheduler;

use Framework\Routing\Attribute\Post;
use Framework\Routing\Attribute\Route;
use Framework\Http\Request;
use Framework\Http\Response;

#[Route('/_schedule')]
class SchedulerRoute
{
    #[Post('/run')]
    public function run(Request $request): Response
    {
        $token = $request->header('x-schedule-token') ?? $request->input('token', '');
        $configToken = config('schedule.route.token', 'change-me');

        if ($token !== $configToken || $configToken === 'change-me') {
            return Response::json(['success' => false, 'error' => 'Invalid token'], 403);
        }

        $scheduler = app(Scheduler::class);
        $dueEvents = $scheduler->due();
        $results = [];

        foreach ($dueEvents as $event) {
            $event->run();
            $results[] = 'Executed';
        }

        return Response::json([
            'success' => true,
            'executed' => count($results),
        ]);
    }
}
