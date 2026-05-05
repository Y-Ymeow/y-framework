<?php

declare(strict_types=1);

namespace Framework\File;

use Framework\Http\Response\Response;
use Framework\Http\Response\StreamedResponse;
use Framework\Http\Request\Request;

class ImageServer
{
    private string $sourceDir;
    private string $cacheDir;

    public function __construct(string $sourceDir, string $cacheDir)
    {
        $this->sourceDir = $sourceDir;
        $this->cacheDir = $cacheDir;
    }

    public function handle(Request $request, string $path): Response
    {
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'media/')) {
            $path = substr($path, 6);
        }

        $fullPath = $this->sourceDir . '/' . $path;
        $realPath = realpath($fullPath);

        if (!$realPath || !str_starts_with($realPath, realpath($this->sourceDir))) {
            return new Response('Image not found', 404);
        }

        if (!file_exists($realPath) || !is_file($realPath)) {
            return new Response('Image not found', 404);
        }

        $params = $request->all();
        $cachePath = $this->resolveCachePath($realPath, $params);

        if ($cachePath && file_exists($cachePath)) {
            return $this->serveFile($cachePath);
        }

        try {
            $processedPath = $this->processImage($realPath, $params, $cachePath);
            return $this->serveFile($processedPath);
        } catch (\Exception $e) {
            return new Response('Image processing error', 500);
        }
    }

    private function serveFile(string $filePath): StreamedResponse
    {
        $mime = $this->mimeType($filePath);
        $fileSize = filesize($filePath);

        return new StreamedResponse(function () use ($filePath) {
            $stream = fopen($filePath, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string)$fileSize,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    private function resolveCachePath(string $sourcePath, array $params): ?string
    {
        if (empty($params)) {
            return null;
        }

        ksort($params);
        $hash = md5($sourcePath . serialize($params));
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);

        return $this->cacheDir . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2) . '.' . $ext;
    }

    private function processImage(string $sourcePath, array $params, ?string $cachePath): string
    {
        if (!extension_loaded('gd')) {
            return $sourcePath;
        }

        $w = (int)($params['w'] ?? 0);
        $h = (int)($params['h'] ?? 0);
        $q = (int)($params['q'] ?? 85);

        if ($w <= 0 && $h <= 0) {
            return $sourcePath;
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                break;
            case 'gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            case 'webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                return $sourcePath;
        }

        if (!$image) {
            return $sourcePath;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);

        if ($w <= 0) {
            $w = (int)($origW * ($h / $origH));
        }
        if ($h <= 0) {
            $h = (int)($origH * ($w / $origW));
        }

        $resized = imagecreatetruecolor($w, $h);

        if ($ext === 'png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $w, $h, $origW, $origH);
        imagedestroy($image);

        if ($cachePath) {
            $dir = dirname($cachePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($resized, $cachePath, $q);
                    break;
                case 'png':
                    imagepng($resized, $cachePath, (int)(9 - ($q / 100 * 9)));
                    break;
                case 'gif':
                    imagegif($resized, $cachePath);
                    break;
                case 'webp':
                    imagewebp($resized, $cachePath, $q);
                    break;
            }

            imagedestroy($resized);
            return $cachePath;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'img_');
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($resized, $tmpFile, $q);
                break;
            case 'png':
                imagepng($resized, $tmpFile);
                break;
            case 'gif':
                imagegif($resized, $tmpFile);
                break;
            case 'webp':
                imagewebp($resized, $tmpFile, $q);
                break;
        }
        imagedestroy($resized);

        return $tmpFile;
    }

    private function mimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
