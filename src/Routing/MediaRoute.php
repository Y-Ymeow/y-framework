<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;

class MediaRoute
{
    private string $uploadDir;
    private string $cacheDir;
    private ?\Framework\File\ImageServer $imageServer = null;

    public function __construct(string $uploadDir, string $cacheDir)
    {
        $this->uploadDir = $uploadDir;
        $this->cacheDir = $cacheDir;
    }

    public function handle(Request $request, string $path): Response
    {
        if (!$this->imageServer) {
            $this->imageServer = new \Framework\File\ImageServer(
                $this->uploadDir,
                $this->cacheDir
            );
        }

        $fullPath = '/media/' . $path;
        return $this->imageServer->handle($request, $fullPath);
    }
}
