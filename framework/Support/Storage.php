<?php

declare(strict_types=1);

namespace Framework\Support;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

class Storage
{
    private static array $disks = [];
    private static array $configs = [];

    public static function configure(array $configs): void
    {
        self::$configs = $configs;
    }

    public static function disk(?string $name = null): Filesystem
    {
        $name ??= self::getDefaultDisk();

        if (isset(self::$disks[$name])) {
            return self::$disks[$name];
        }

        $config = self::$configs[$name] ?? self::getDiskConfig($name);
        
        return self::$disks[$name] = self::createFilesystem($config);
    }

    public static function put(string $path, mixed $contents, array $config = []): bool
    {
        try {
            self::disk()->write($path, $contents, $config);
            return true;
        } catch (UnableToWriteFile) {
            return false;
        }
    }

    public static function get(string $path): ?string
    {
        try {
            return self::disk()->read($path);
        } catch (UnableToReadFile) {
            return null;
        }
    }

    public static function exists(string $path): bool
    {
        return self::disk()->fileExists($path);
    }

    public static function missing(string $path): bool
    {
        return !self::exists($path);
    }

    public static function delete(string $path): bool
    {
        try {
            self::disk()->delete($path);
            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    public static function copy(string $from, string $to): bool
    {
        try {
            self::disk()->copy($from, $to);
            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    public static function move(string $from, string $to): bool
    {
        try {
            self::disk()->move($from, $to);
            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    public static function size(string $path): int
    {
        return self::disk()->fileSize($path);
    }

    public static function lastModified(string $path): int
    {
        return self::disk()->lastModified($path);
    }

    public static function url(string $path, ?string $disk = null): string
    {
        $disk ??= self::getDefaultDisk();
        $baseUrl = self::$configs[$disk]['url'] ?? config('app.url', '');
        return rtrim($baseUrl, '/') . '/storage/' . ltrim($path, '/');
    }

    public static function mediaUrl(string $path): string
    {
        $baseUrl = config('app.url', '');
        return rtrim($baseUrl, '/') . '/media/' . ltrim($path, '/');
    }

    public static function downloadUrl(string $path): string
    {
        $baseUrl = config('app.url', '');
        return rtrim($baseUrl, '/') . '/download/' . ltrim($path, '/');
    }

    public static function streamUrl(string $path): string
    {
        $baseUrl = config('app.url', '');
        return rtrim($baseUrl, '/') . '/stream/' . ltrim($path, '/');
    }

    public static function path(string $path, ?string $disk = null): string
    {
        $disk ??= self::getDefaultDisk();
        $root = self::$configs[$disk]['root'] ?? storage_path('app');
        return rtrim($root, '/') . '/' . ltrim($path, '/');
    }

    public static function upload(string $path, mixed $file, ?string $disk = null): bool
    {
        $diskInstance = $disk ? self::disk($disk) : self::disk();
        
        try {
            $diskInstance->write($path, $file);
            return true;
        } catch (UnableToWriteFile) {
            return false;
        }
    }

    public static function mimeType(string $path): string
    {
        return self::disk()->mimeType($path);
    }

    public static function files(string $directory = ''): array
    {
        $contents = self::disk()->listContents($directory);
        $files = [];
        
        foreach ($contents as $item) {
            if ($item->type() === 'file') {
                $files[] = $item->path();
            }
        }
        
        return $files;
    }

    public static function directories(string $directory = ''): array
    {
        $contents = self::disk()->listContents($directory);
        $dirs = [];
        
        foreach ($contents as $item) {
            if ($item->type() === 'dir') {
                $dirs[] = $item->path();
            }
        }
        
        return $dirs;
    }

    public static function makeDirectory(string $path): bool
    {
        try {
            self::disk()->createDirectory($path);
            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    public static function deleteDirectory(string $path): bool
    {
        try {
            self::disk()->deleteDirectory($path);
            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    private static function getDefaultDisk(): string
    {
        return config('filesystems.default', 'local');
    }

    private static function getDiskConfig(string $name): array
    {
        return config("filesystems.disks.{$name}", [
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);
    }

    private static function createFilesystem(array $config): Filesystem
    {
        $driver = $config['driver'] ?? 'local';

        if ($driver === 'local') {
            $root = $config['root'] ?? storage_path('app');
            $adapter = new LocalFilesystemAdapter($root);
            return new Filesystem($adapter);
        }

        throw new \InvalidArgumentException("Unsupported filesystem driver: {$driver}");
    }
}
