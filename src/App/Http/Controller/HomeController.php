<?php

declare(strict_types=1);

namespace Framework\App\Http\Controller;

use Framework\Core\Application;
use Framework\Http\Request;
use Framework\Routing\Attribute\Route;

final class HomeController
{
    #[Route('/', methods: ['GET'], name: 'home')]
    public function index(Request $request, Application $app): string
    {
        $name = (string) $app->config()->get('app.name', 'Framework');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$name}</title>
</head>
<body>
    <h1>{$name}</h1>
    <p>Framework skeleton is running.</p>
    <p>Route cache: <code>storage/cache/routes.php</code></p>
</body>
</html>
HTML;
    }

    #[Route('/health', methods: ['GET'], name: 'health')]
    public function health(Request $request, Application $app): array
    {
        return [
            'name' => $app->config()->get('app.name', 'Framework'),
            'status' => 'ok',
            'path' => $request->uri(),
        ];
    }
}
