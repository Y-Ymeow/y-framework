<?php

declare(strict_types=1);

namespace Framework\Filesystem;

class Storage
{
    private static array $disks = [];

    public static function disk(string $name = 'local'): Filesystem
    {
        if (!isset(self::$disks[$name])) {
            $config = config("filesystems.disks.{$name}", []);
            self::$disks[$name] = new Filesystem($config);
        }

        return self::$disks[$name];
    }

    public static function put(string $path, string $content, mixed $options = []): bool
    {
        return self::disk()->put($path, $content, $options);
    }

    public static function get(string $path): string|false
    {
        return self::disk()->get($path);
    }

    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }

    public static function delete(string $path): bool
    {
        return self::disk()->delete($path);
    }

    public static function url(string $path): string
    {
        return self::disk()->url($path);
    }

    public static function files(string $directory = ''): array
    {
        return self::disk()->files($directory);
    }

    public static function allFiles(string $directory = ''): array
    {
        return self::disk()->allFiles($directory);
    }

    public static function directories(string $directory = ''): array
    {
        return self::disk()->directories($directory);
    }

    public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        return self::disk()->makeDirectory($path, $mode, $recursive);
    }

    public static function deleteDirectory(string $path): bool
    {
        return self::disk()->deleteDirectory($path);
    }

    public static function lastModified(string $path): int
    {
        return self::disk()->lastModified($path);
    }

    public static function size(string $path): int
    {
        return self::disk()->size($path);
    }

    public static function mimeType(string $path): string
    {
        return self::disk()->mimeType($path);
    }
}

class Filesystem
{
    private string $root;
    private string $url;

    public function __construct(array $config)
    {
        $this->root = rtrim($config['root'] ?? storage_path('app'), '/');
        $this->url = rtrim($config['url'] ?? '/storage', '/');

        if (!is_dir($this->root)) {
            mkdir($this->root, 0755, true);
        }
    }

    public function put(string $path, string $content, mixed $options = []): bool
    {
        $fullPath = $this->path($path);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($fullPath, $content) !== false;
    }

    public function putFile(string $path, $file, mixed $options = []): string|false
    {
        if (is_object($file) && method_exists($file, 'getPathname')) {
            $content = file_get_contents($file->getPathname());
        } else {
            $content = file_get_contents($file);
        }

        if ($content === false) {
            return false;
        }

        $this->put($path, $content, $options);
        return $path;
    }

    public function get(string $path): string|false
    {
        $fullPath = $this->path($path);
        if (!is_file($fullPath)) {
            return false;
        }

        return file_get_contents($fullPath);
    }

    public function exists(string $path): bool
    {
        return is_file($this->path($path));
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->path($path);
        if (is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function url(string $path): string
    {
        return $this->url . '/' . ltrim($path, '/');
    }

    public function files(string $directory = ''): array
    {
        $path = $this->path($directory);
        if (!is_dir($path)) {
            return [];
        }

        return array_values(array_filter(
            scandir($path),
            fn($f) => is_file($path . '/' . $f) && $f !== '.' && $f !== '..'
        ));
    }

    public function allFiles(string $directory = ''): array
    {
        $path = $this->path($directory);
        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $this->scanDirectory($path, '', $files);
        return $files;
    }

    public function directories(string $directory = ''): array
    {
        $path = $this->path($directory);
        if (!is_dir($path)) {
            return [];
        }

        return array_values(array_filter(
            scandir($path),
            fn($d) => is_dir($path . '/' . $d) && $d !== '.' && $d !== '..'
        ));
    }

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        return mkdir($this->path($path), $mode, $recursive);
    }

    public function deleteDirectory(string $path): bool
    {
        $fullPath = $this->path($path);
        if (!is_dir($fullPath)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        return rmdir($fullPath);
    }

    public function lastModified(string $path): int
    {
        return filemtime($this->path($path));
    }

    public function size(string $path): int
    {
        return filesize($this->path($path));
    }

    public function mimeType(string $path): string
    {
        $fullPath = $this->path($path);
        $mime = mime_content_type($fullPath);
        return $mime ?: 'application/octet-stream';
    }

    public function path(string $path): string
    {
        return $this->root . '/' . ltrim($path, '/');
    }

    private function scanDirectory(string $dir, string $prefix, array &$files): void
    {
        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $fullPath = $dir . '/' . $item;
            $relativePath = $prefix . $item;

            if (is_file($fullPath)) {
                $files[] = $relativePath;
            } elseif (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $relativePath . '/', $files);
            }
        }
    }
}
