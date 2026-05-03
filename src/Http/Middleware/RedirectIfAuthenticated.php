<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, callable $next, string $redirectToRoute = 'home'): Response
    {
        if (auth()->check()) {
            return redirect($redirectToRoute);
        }

        return $next($request);
    }
}
