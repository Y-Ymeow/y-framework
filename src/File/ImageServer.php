<?php

declare(strict_types=1);

namespace Framework\File;

use League\Glide\ServerFactory;
use Framework\Http\Response;
use Framework\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageServer
{
    private $server;

    public function __construct(string $sourceDir, string $cacheDir)
    {
        $this->server = ServerFactory::create([
            'source' => $sourceDir,
            'cache' => $cacheDir,
            'driver' => 'gd', // 或者 'imagick'
        ]);
    }

    public function handle(Request $request, string $path): Response
    {
        // 去掉前面的路径前缀，比如 /media/
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'media/')) {
            $path = substr($path, 6);
        }

        try {
            // Glide 会根据请求参数自动处理图片
            $params = $request->all();
            
            // 获取 Symfony 的 StreamedResponse
            $sfResponse = $this->server->getImageResponse($path, $params);
            
            return Response::fromSymfony($sfResponse);
        } catch (\Exception $e) {
            return new Response('Image not found', 404);
        }
    }
}
