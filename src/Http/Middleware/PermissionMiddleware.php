<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class PermissionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        $permissions = $params;

        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect(route('login'));
        }

        if (!auth()->hasPermission($permissions)) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Forbidden: insufficient permission.'], 403);
            }
            abort(403, '没有权限执行此操作');
        }

        return $next($request);
    }
}
