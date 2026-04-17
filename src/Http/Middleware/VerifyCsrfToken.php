<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\MiddlewareInterface;
use Framework\Http\Request;
use Framework\Http\RequestHandlerInterface;
use Framework\Http\Response;
use Framework\Http\Session;

final class VerifyCsrfToken implements MiddlewareInterface
{
    public function __construct(
        private readonly Session $session
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $handler->handle($request);
        }

        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token || $token !== $this->session->token()) {
            return new Response("CSRF token mismatch.", 403);
        }

        return $handler->handle($request);
    }
}
