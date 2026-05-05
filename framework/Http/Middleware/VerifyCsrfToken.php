<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Foundation\AppEnvironment;
use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Session\Session;

class VerifyCsrfToken implements MiddlewareInterface
{
    /**
     * 无需 CSRF 保护的 HTTP 方法
     */
    private const INSECURE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        if (AppEnvironment::isWasm()) {
            return $next($request);
        }

        if (in_array($request->method(), self::INSECURE_METHODS, true)) {
            return $next($request);
        }

        $session = app()->make(Session::class);
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN', '');

        if (!$session->verifyToken((string) $token)) {
            return Response::json(['message' => 'CSRF token mismatch.'], 419);
        }

        return $next($request);
    }
}
