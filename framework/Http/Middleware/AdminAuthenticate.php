<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\RedirectResponse;

class AdminAuthenticate implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        error_log(!auth()->check() ? 'not authenticated' : 'authenticated');
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Unauthenticated.'], 401);
            }

            return new RedirectResponse('/admin/login');
        }

        return $next($request);
    }
}
