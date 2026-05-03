<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class Authenticate
{
    public function handle(Request $request, callable $next, string $redirectToRoute = 'login'): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Unauthenticated.'], 401);
            }

            $url = route($redirectToRoute);
            return redirect($url);
        }

        return $next($request);
    }
}
