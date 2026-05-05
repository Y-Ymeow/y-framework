<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\StaticFile;

class StaticAssetsRoute
{
    private string $basePath;
    private array $directoryMap;
    private array $allowedDomains;
    private ?StaticFile $staticFile = null;

    public function __construct(string $basePath, array $directoryMap = [], array $allowedDomains = [])
    {
        $this->basePath = $basePath;
        $this->directoryMap = $directoryMap ?: [
            '/assets/build' => '/public/build',
            '/assets/images' => '/public/assets/images',
            '/assets/videos' => '/public/assets/videos',
            '/assets/files' => '/public/assets/files',
            '/assets/css' => '/public/assets/css',
            '/assets/libs' => '/public/libs',
        ];
        $this->allowedDomains = $allowedDomains;
    }

    public function handle(Request $request, string $path): Response
    {
        $host = $request->host();
        $domains = $this->allowedDomains ?: ['localhost', '127.0.0.1', $host];

        $static = new StaticFile();

        foreach ($this->directoryMap as $routePrefix => $dirPath) {
            $static->addDir($routePrefix, $this->basePath . $dirPath);
        }

        $static->allowDomains($domains);

        return $static->serve('/assets/' . $path, $host);
    }
}
