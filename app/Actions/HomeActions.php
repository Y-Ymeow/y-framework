<?php

declare(strict_types=1);

namespace App\Actions;

use Framework\Core\Application;
use Framework\Http\Request;

function hello(string $name, Request $request, Application $app): array
{
    return [
        'message' => 'hello ' . $name,
        'framework' => $app->config()->get('app.name', 'Framework'),
        'path' => $request->uri(),
    ];
}

function sqlPing(): array
{
    $row = sql('SELECT 1 AS ok')[0] ?? ['ok' => 0];

    return [
        'database' => $row,
    ];
}
