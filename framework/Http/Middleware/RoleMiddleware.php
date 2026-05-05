<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class RoleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        $roles = $params;

        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect(route('login'));
        }

        if (!auth()->hasRole($roles)) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Forbidden: insufficient role.'], 403);
            }
            abort(403, '没有权限访问此页面');
        }

        return $next($request);
    }
}
