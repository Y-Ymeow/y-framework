<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Foundation\AppEnvironment;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;

class VerifyCsrfToken
{
    public function handle(Request $request, callable $next): Response
    {
        if (AppEnvironment::isWasm()) {
            return $next($request);
        }

        $insecureMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (in_array($request->method(), $insecureMethods, true)) {
            return $next($request);
        }

        $session = new Session();
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN', '');

        if (!$session->verifyToken((string) $token)) {
            return Response::json(['message' => 'CSRF token mismatch.'], 419);
        }

        return $next($request);
    }
}
