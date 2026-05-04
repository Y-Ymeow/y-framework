<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class ThrottleRequests implements MiddlewareInterface
{
    private static array $requests = [];

    /**
     * 默认限流配置：每分钟 60 次
     */
    private const DEFAULT_MAX_ATTEMPTS = 60;
    private const DEFAULT_DECAY_MINUTES = 1;

    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        $maxAttempts = (int) ($params[0] ?? self::DEFAULT_MAX_ATTEMPTS);
        $decayMinutes = (int) ($params[1] ?? self::DEFAULT_DECAY_MINUTES);

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

