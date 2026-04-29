<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\StreamedResponse;
use Framework\Routing\Attribute\Route;
use Framework\View\Document\AssetRegistry;

#[Route('', name: 'system')]
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

    #[Route('/_css', methods: ['GET'], name: 'css')]
    public function css(Request $request): Response
    {
        $debug = config('app.debug', true);
        $route = new CssRoute($this->basePath, $debug);
        return $route->handle($request);
    }

    #[Route('/_js', methods: ['GET'], name: 'js')]
    public function js(Request $request): Response
    {
        $idsStr = $request->input('ids', '');
        if (empty($idsStr)) {
            return new Response('', 200, ['Content-Type' => 'application/javascript']);
        }

        $ids = explode(',', $idsStr);
        $registry = AssetRegistry::getInstance();
        $js = "/* Y-Framework Dynamic JS Resource */\n\n";

        foreach ($ids as $id) {
            // 首先从 Registry 内存尝试获取
            $content = $registry->getScriptContent($id);
            
            // 如果内存没有，从缓存读取
            if ($content === null && function_exists('cache')) {
                $content = cache()->get('js_resource:' . $id);
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
}
