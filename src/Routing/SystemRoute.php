<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\StreamedResponse;

class SystemRoute
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public static function __set_state(array $array): self
    {
        return new self($array['basePath']);
    }

    public function media(Request $request, string $path): Response
    {
        $route = new MediaRoute(
            $this->basePath . '/storage/uploads',
            $this->basePath . '/storage/cache/images'
        );
        return $route->handle($request, $path);
    }

    public function assets(Request $request, string $path): Response|StreamedResponse
    {
        $route = new StaticAssetsRoute($this->basePath);
        return $route->handle($request, $path);
    }

    public function download(Request $request, string $path): Response|StreamedResponse
    {
        $route = new FileDownloadRoute($this->basePath . '/storage/files');
        return $route->handle($request, $path, true);
    }

    public function stream(Request $request, string $path): Response|StreamedResponse
    {
        $route = new FileDownloadRoute($this->basePath . '/storage/files');
        return $route->handle($request, $path, false);
    }

    public function css(Request $request): Response
    {
        $debug = config('app.debug', true);
        $route = new CssRoute($this->basePath, $debug);
        return $route->handle($request);
    }

    public function js(Request $request): Response
    {
        return new Response('', 200, ['Content-Type' => 'application/javascript']);
    }
}
