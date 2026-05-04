<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        $redirectToRoute = $params[0] ?? 'home';

        if (auth()->check()) {
            return redirect($redirectToRoute);
        }

        return $next($request);
    }
}
