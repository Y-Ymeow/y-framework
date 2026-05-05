<?php

declare(strict_types=1);

namespace Framework\Error;

use Framework\View\Base\Element;

class ErrorPage
{
    private static array $titles = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        408 => 'Request Timeout',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    private static array $descriptions = [
        400 => 'The server could not understand the request.',
        401 => 'Authentication is required to access this resource.',
        403 => 'You do not have permission to access this resource.',
        404 => 'The page you are looking for could not be found.',
        405 => 'The request method is not allowed for this URL.',
        408 => 'The server timed out waiting for the request.',
        429 => 'You have made too many requests. Please try again later.',
        500 => 'An internal server error occurred.',
        502 => 'The server received an invalid response from an upstream server.',
        503 => 'The server is temporarily unable to handle this request.',
        504 => 'The upstream server did not respond in time.',
    ];

    private static array $icons = [
        400 => 'bi-exclamation-circle',
        401 => 'bi-lock',
        403 => 'bi-shield-exclamation',
        404 => 'bi-compass',
        405 => 'bi-slash-circle',
        408 => 'bi-clock',
        429 => 'bi-speedometer2',
        500 => 'bi-bug',
        502 => 'bi-diagram-3',
        503 => 'bi-tools',
        504 => 'bi-hourglass-split',
    ];

    public static function render(int $code, ?string $message = null, bool $showHome = true): string
    {
        $title = self::$titles[$code] ?? 'Error';
        $description = $message ?? (self::$descriptions[$code] ?? 'An unexpected error occurred.');
        $icon = self::$icons[$code] ?? 'bi-exclamation-triangle';

        $homeLink = '';
        if ($showHome) {
            $homeUrl = '/admin';
            $homeLink = <<<HTML
<a href="{$homeUrl}" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;background:#3b82f6;color:#fff;border-radius:0.5rem;text-decoration:none;font-weight:500;font-size:0.875rem;transition:background 0.15s;margin-top:1.5rem" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1l7 6v8h-4v-4H5v4H1V7l7-6z"/></svg>
Back to Dashboard
</a>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$code} {$title}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem}
.container{text-align:center;max-width:32rem}
.icon-wrap{width:5rem;height:5rem;border-radius:50%;background:#eff6ff;display:inline-flex;align-items:center;justify-content:center;margin-bottom:1.5rem}
.icon-wrap i{font-size:2rem;color:#3b82f6}
.error-code{font-size:6rem;font-weight:900;background:linear-gradient(135deg,#e2e8f0,#cbd5e1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;margin-bottom:0.5rem}
.error-title{font-size:1.5rem;font-weight:700;color:#334155;margin-bottom:0.75rem}
.error-desc{font-size:1rem;color:#64748b;line-height:1.6;margin-bottom:1rem}
.error-actions{display:flex;gap:0.75rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;border-radius:0.5rem;text-decoration:none;font-weight:500;font-size:0.875rem;transition:all 0.15s;cursor:pointer;border:none}
.btn-primary{background:#3b82f6;color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-secondary{background:#fff;color:#374151;border:1px solid #d1d5db}
.btn-secondary:hover{background:#f9fafb}
.footer{margin-top:3rem;font-size:0.75rem;color:#94a3b8}
</style>
</head>
<body>
<div class="container">
<div class="icon-wrap"><i class="bi {$icon}"></i></div>
<div class="error-code">{$code}</div>
<div class="error-title">{$title}</div>
<div class="error-desc">{$description}</div>
<div class="error-actions">
{$homeLink}
<a href="javascript:history.back()" class="btn btn-secondary">
<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 00-.5-.5H2.707l3.147-3.146a.5.5 0 00-.708-.708l-4 4a.5.5 0 000 .708l4 4a.5.5 0 00.708-.708L2.707 8.5H14.5A.5.5 0 0015 8z"/></svg>
Go Back
</a>
</div>
<div class="footer">If you believe this is an error, please contact the administrator.</div>
</div>
</body>
</html>
HTML;
    }

    public static function renderElement(int $code, ?string $message = null): Element
    {
        $title = self::$titles[$code] ?? 'Error';
        $description = $message ?? (self::$descriptions[$code] ?? 'An unexpected error occurred.');
        $icon = self::$icons[$code] ?? 'bi-exclamation-triangle';

        $wrapper = Element::make('div')->class('flex', 'items-center', 'justify-center', 'min-h-[60vh]', 'p-8');
        $container = Element::make('div')->class('text-center', 'max-w-xl');

        $iconWrap = Element::make('div')->class('w-20', 'h-20', 'rounded-full', 'bg-blue-50', 'inline-flex', 'items-center', 'justify-center', 'mb-6');
        $iconWrap->child(Element::make('i')->class('bi', $icon, 'text-3xl', 'text-blue-500'));
        $container->child($iconWrap);

        $container->child(Element::make('div')->class('text-6xl', 'font-black', 'text-gray-200', 'mb-2')->text((string)$code));
        $container->child(Element::make('h2')->class('text-xl', 'font-bold', 'text-gray-800', 'mb-3')->text($title));
        $container->child(Element::make('p')->class('text-gray-500', 'mb-6')->text($description));

        $actions = Element::make('div')->class('flex', 'gap-3', 'justify-center');
        $actions->child(
            Element::make('a')
                ->class('admin-btn', 'admin-btn-primary')
                ->attr('href', '/admin')
                ->text('Back to Dashboard')
        );
        $actions->child(
            Element::make('a')
                ->class('admin-btn', 'admin-btn-secondary')
                ->attr('href', 'javascript:history.back()')
                ->text('Go Back')
        );
        $container->child($actions);

        $wrapper->child($container);
        return $wrapper;
    }
}
