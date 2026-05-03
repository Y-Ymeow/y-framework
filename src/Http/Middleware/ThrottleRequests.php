<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class ThrottleRequests
{
    private static array $requests = [];

    public function handle(Request $request, callable $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $request->ip();
        $now = time();
        $decaySeconds = $decayMinutes * 60;

        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = ['count' => 0, 'time' => $now];
        }

        $record = &self::$requests[$key];

        if ($now - $record['time'] > $decaySeconds) {
            $record = ['count' => 0, 'time' => $now];
        }

        $record['count']++;

        if ($record['count'] > $maxAttempts) {
            $retryAfter = $decaySeconds - ($now - $record['time']);
            $headers = [
                'Retry-After' => max($retryAfter, 0),
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ];

            return Response::json(['message' => 'Too Many Attempts.'], 429, $headers);
        }

        $response = $next($request);

        $remaining = max(0, $maxAttempts - $record['count']);
        $response->header('X-RateLimit-Limit', $maxAttempts);
        $response->header('X-RateLimit-Remaining', $remaining);

        return $response;
    }
}
