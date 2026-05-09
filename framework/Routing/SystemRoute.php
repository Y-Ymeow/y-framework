<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\StreamedResponse;
use Framework\Http\StaticFile;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;
use Framework\View\Document\AssetRegistry;

#[RouteGroup('', name: 'system')]
class SystemRoute
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = base_path();
    }

    public static function __set_state(array $array): self
    {
        return new self();
    }

    #[Route('/media/{path...}', methods: ['GET'], name: 'media')]
    public function media(Request $request, string $path): Response
    {
        $route = new MediaRoute(
            paths()->uploads(),
            paths()->cache('images')
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
        $route = new FileDownloadRoute(paths()->files());
        return $route->handle($request, $path, true);
    }

    public function stream(Request $request, string $path): Response|StreamedResponse
    {
        $route = new FileDownloadRoute(paths()->files());
        return $route->handle($request, $path, false);
    }

    #[Route('/_css/v/{v}/{snippets...}', methods: ['GET'], name: 'css_v')]
    #[Route('/_css', methods: ['GET'], name: 'css')]
    public function css(Request $request, ?string $snippets = null): Response
    {
        $debug = \Framework\Foundation\Application::isDebug();
        $route = new CssRoute($this->basePath, $debug);
        return $route->handle($request, $snippets);
    }

    #[Route('/_js/v/{v}/{ids...}', methods: ['GET'], name: 'js_v')]
    #[Route('/_js/{ids...}', methods: ['GET'], name: 'js')]
    public function js(Request $request, ?string $ids = null): Response
    {
        $idsStr = $ids ?? $request->input('ids', '');
        if (empty($idsStr)) {
            return new Response('', 200, ['Content-Type' => 'application/javascript']);
        }

        if (str_ends_with($idsStr, '.js')) {
            $idsStr = substr($idsStr, 0, -3);
        }

        $ids = array_values(array_filter(
            array_unique(explode(',', urldecode($idsStr))),
            static fn ($id) => is_string($id) && preg_match('/^[A-Za-z0-9:._-]+$/', $id) === 1
        ));

        if (empty($ids)) {
            return new Response('', 200, ['Content-Type' => 'application/javascript']);
        }

        $registry = AssetRegistry::getInstance();
        $js = "/* Y-Framework Dynamic JS Resource */\n\n";

        foreach ($ids as $id) {
            $content = $registry->getScriptContent($id);

            if ($content === null && function_exists('\\cache')) {
                $content = \cache()->get('js_resource:' . $id);
            }

            if ($content) {
                $js .= "/* --- ID: {$id} --- */\n";
                $js .= $content . "\n\n";
            }
        }

        return new Response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    #[Route('/_framework/{path...}', methods: ['GET'], name: 'dist')]
    public function dist(Request $request, ?string $path = ''): Response|StreamedResponse
    {
        $path = $path ?? '';
        if (empty($path) || $path === '/') {
            return new Response(\Framework\Error\ErrorPage::render(404), 404);
        }

        if (str_contains($path, 'resources')) {
            $path = str_replace('resources/', '', $path);
        }

        $distPath = \Framework\Support\Asset::distPath();
        $static = new StaticFile($distPath);
        $static->disableHotlinkProtection();
        return $static->serve('/' . $path, $request->host());
    }
}
