<?php

declare(strict_types=1);

use Framework\Http\Response;

if (! function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers === [] ? ['Content-Type' => 'text/html; charset=UTF-8'] : $headers);
    }
}

if (! function_exists('json')) {
    function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (! function_exists('session')) {
    function session(): \Framework\Http\Session
    {
        return app(\Framework\Http\Session::class);
    }
}

if (! function_exists('user')) {
    function user(): ?object
    {
        return session()->get('user');
    }
}
