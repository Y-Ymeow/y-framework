<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\StreamedResponse;
use Framework\View\Base\Element;

class FileDownloadRoute
{
    private string $storagePath;
    private array $allowedExtensions = [];
    private int $chunkSize = 8192;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
    }

    public function allowExtensions(array $extensions): self
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    public function handle(Request $request, string $path, bool $forceDownload = false): Response|StreamedResponse
    {
        $filePath = $this->resolvePath($path);

        if (!$filePath || !file_exists($filePath) || !is_file($filePath)) {
            return Response::html(Element::make('h1', '404 Not Found'), 404);
        }

        if (!$this->isAllowed($filePath)) {
            return Response::html('<h1>403 Forbidden</h1>', 403);
        }

        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        $etag = $this->generateEtag($filePath, $lastModified);

        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return new Response('', 304);
        }

        $ifModifiedSince = $request->header('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return new Response('', 304);
        }

        $range = $request->header('Range');
        $headers = $this->buildHeaders($filePath, $fileSize, $lastModified, $etag, $forceDownload);

        if ($range) {
            return $this->handleRangeRequest($filePath, $fileSize, $range, $headers);
        }

        return new StreamedResponse(function () use ($filePath) {
            $this->streamFile($filePath);
        }, 200, $headers);
    }

    private function resolvePath(string $path): ?string
    {
        $path = '/' . ltrim($path, '/');
        $fullPath = $this->storagePath . $path;
        $realPath = realpath($fullPath);

        if ($realPath && str_starts_with($realPath, realpath($this->storagePath))) {
            return $realPath;
        }

        return null;
    }

    private function isAllowed(string $filePath): bool
    {
        if (empty($this->allowedExtensions)) {
            return true;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, $this->allowedExtensions, true);
    }

    private function buildHeaders(string $filePath, int $fileSize, int $lastModified, string $etag, bool $forceDownload): array
    {
        $mime = $this->mimeType($filePath);
        $fileName = basename($filePath);

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string)$fileSize,
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Cache-Control' => 'public, max-age=31536000',
            'Accept-Ranges' => 'bytes',
        ];

        if ($forceDownload) {
            $headers['Content-Disposition'] = 'attachment; filename="' . $fileName . '"';
        } else {
            $headers['Content-Disposition'] = 'inline; filename="' . $fileName . '"';
        }

        return $headers;
    }

    private function handleRangeRequest(string $filePath, int $fileSize, string $range, array $baseHeaders): StreamedResponse
    {
        $ranges = $this->parseRangeHeader($range, $fileSize);

        if (empty($ranges)) {
            return new StreamedResponse(function () {
            }, 416, ['Content-Range' => 'bytes */' . $fileSize]);
        }

        if (count($ranges) === 1) {
            return $this->handleSingleRange($filePath, $fileSize, $ranges[0], $baseHeaders);
        }

        return $this->handleMultipleRanges($filePath, $fileSize, $ranges, $baseHeaders);
    }

    private function parseRangeHeader(string $range, int $fileSize): array
    {
        if (!preg_match('/^bytes=(.+)$/i', trim($range), $matches)) {
            return [];
        }

        $ranges = [];
        $parts = explode(',', $matches[1]);

        foreach ($parts as $part) {
            $part = trim($part);
            if (!preg_match('/^(\d*)-(\d*)$/', $part, $rangeMatch)) {
                continue;
            }

            $start = $rangeMatch[1] !== '' ? (int)$rangeMatch[1] : null;
            $end = $rangeMatch[2] !== '' ? (int)$rangeMatch[2] : null;

            if ($start === null && $end === null) {
                continue;
            }

            if ($start === null) {
                $start = max(0, $fileSize - $end);
                $end = $fileSize - 1;
            } elseif ($end === null) {
                $end = $fileSize - 1;
            }

            if ($start > $end || $start >= $fileSize) {
                continue;
            }

            $end = min($end, $fileSize - 1);
            $ranges[] = ['start' => $start, 'end' => $end];
        }

        return $ranges;
    }

    private function handleSingleRange(string $filePath, int $fileSize, array $range, array $baseHeaders): StreamedResponse
    {
        $start = $range['start'];
        $end = $range['end'];
        $length = $end - $start + 1;

        $headers = $baseHeaders;
        $headers['Content-Length'] = (string)$length;
        $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";

        return new StreamedResponse(function () use ($filePath, $start, $end) {
            $this->streamFileRange($filePath, $start, $end);
        }, 206, $headers);
    }

    private function handleMultipleRanges(string $filePath, int $fileSize, array $ranges, array $baseHeaders): StreamedResponse
    {
        $boundary = 'BOUNDARY-' . md5(uniqid('', true));
        $headers = $baseHeaders;
        $headers['Content-Type'] = 'multipart/byteranges; boundary=' . $boundary;

        return new StreamedResponse(function () use ($filePath, $fileSize, $ranges, $boundary) {
            $mime = $this->mimeType($filePath);
            foreach ($ranges as $range) {
                echo "--{$boundary}\r\n";
                echo "Content-Type: {$mime}\r\n";
                echo "Content-Range: bytes {$range['start']}-{$range['end']}/{$fileSize}\r\n\r\n";
                $this->streamFileRange($filePath, $range['start'], $range['end']);
                echo "\r\n";
            }
            echo "--{$boundary}--\r\n";
        }, 206, $headers);
    }

    private function streamFile(string $filePath): void
    {
        $stream = fopen($filePath, 'rb');
        while (!feof($stream)) {
            echo fread($stream, $this->chunkSize);
            flush();
        }
        fclose($stream);
    }

    private function streamFileRange(string $filePath, int $start, int $end): void
    {
        $stream = fopen($filePath, 'rb');
        fseek($stream, $start);

        $remaining = $end - $start + 1;
        while ($remaining > 0 && !feof($stream)) {
            $chunk = min($this->chunkSize, $remaining);
            echo fread($stream, $chunk);
            $remaining -= $chunk;
            flush();
        }
        fclose($stream);
    }

    private function generateEtag(string $filePath, int $lastModified): string
    {
        return '"' . md5($filePath . $lastModified . filesize($filePath)) . '"';
    }

    private function mimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/octet-stream',
        };
    }
}
