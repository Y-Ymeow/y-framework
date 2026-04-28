<?php

declare(strict_types=1);

namespace Framework\Http;

class StaticFile
{
    private array $dirs = [];
    private string $defaultDir = '';
    private array $allowedDomains = [];
    private bool $enableHotlinkProtection = true;

    public function __construct(string $defaultDir = '')
    {
        $this->defaultDir = $defaultDir;
    }

    public function addDir(string $prefix, string $dir): self
    {
        $this->dirs[$prefix] = $dir;
        return $this;
    }

    public function allowDomains(array $domains): self
    {
        $this->allowedDomains = $domains;
        return $this;
    }

    public function disableHotlinkProtection(): self
    {
        $this->enableHotlinkProtection = false;
        return $this;
    }

    public function serve(string $path, ?string $host = null)
    {
        $filePath = $this->resolve($path);

        if (!$filePath || !file_exists($filePath) || !is_file($filePath)) {
            throw new HttpException(404, 'File not found');
        }

        if ($this->enableHotlinkProtection && $host) {
            $this->checkHotlink($host);
        }

        $mime = $this->mimeType($filePath);
        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        $etag = $this->generateEtag($filePath, $lastModified);

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string)$fileSize,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Vary' => 'Accept-Encoding',
        ];

        if (!empty($this->allowedDomains)) {
            $headers['Access-Control-Allow-Origin'] = implode(', ', $this->allowedDomains);
            $headers['Access-Control-Allow-Methods'] = 'GET, HEAD, OPTIONS';
            $headers['Access-Control-Allow-Headers'] = 'Accept-Encoding';
            $headers['Access-Control-Max-Age'] = '86400';
        }

        $request = Request::createFromGlobals();
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return new Response('', 304, $headers);
        }

        $ifModifiedSince = $request->header('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return new Response('', 304, $headers);
        }

        return new StreamedResponse(function() use ($filePath) {
            $stream = fopen($filePath, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            fclose($stream);
        }, 200, $headers);
    }

    private function checkHotlink(string $host): void
    {
        $request = Request::createFromGlobals();
        $referer = $request->header('Referer');

        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost && $refererHost !== $host) {
                $allowed = false;
                foreach ($this->allowedDomains as $domain) {
                    if (fnmatch($domain, $refererHost) || $refererHost === $domain) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed && !empty($this->allowedDomains)) {
                    throw new HttpException(403, 'Forbidden');
                }
            }
        }
    }

    private function generateEtag(string $filePath, int $lastModified): string
    {
        return '"' . md5($filePath . $lastModified . filesize($filePath)) . '"';
    }

    private function resolve(string $path): ?string
    {
        $path = '/' . ltrim($path, '/');

        foreach ($this->dirs as $prefix => $dir) {
            if (str_starts_with($path, $prefix)) {
                $relativePath = substr($path, strlen($prefix));
                $filePath = $dir . $relativePath;
                $realPath = realpath($filePath);

                if ($realPath && str_starts_with($realPath, realpath($dir))) {
                    return $realPath;
                }
            }
        }

        if ($this->defaultDir) {
            $filePath = $this->defaultDir . $path;
            $realPath = realpath($filePath);

            if ($realPath && str_starts_with($realPath, realpath($this->defaultDir))) {
                return $realPath;
            }
        }

        return null;
    }

    private function mimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'js', 'mjs' => 'application/javascript',
            'css' => 'text/css',
            'json' => 'application/json',
            'html', 'htm' => 'text/html',
            'txt' => 'text/plain',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'zip' => 'application/zip',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'audio/ogg',
            'mp3' => 'audio/mpeg',
            'wasm' => 'application/wasm',
            default => 'application/octet-stream',
        };
    }
}
