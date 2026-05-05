<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class Authenticate implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        $redirectToRoute = $params[0] ?? 'login';

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
