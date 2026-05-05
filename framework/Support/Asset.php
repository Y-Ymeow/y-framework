<?php

declare(strict_types=1);

namespace Framework\Support;

class Asset
{
    private static ?array $manifest = null;
    private static ?bool $isDev = null;
    private static ?string $distPath = null;
    private static ?string $distUrl = null;

    public static function isDev(): bool
    {
        if (self::$isDev !== null) {
            return self::$isDev;
        }

        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 0.1);
        if ($socket) {
            fclose($socket);
            return self::$isDev = true;
        }

        return self::$isDev = false;
    }

    public static function distPath(): string
    {
        var_dump(base_path('public/build/.vite/manifest.json'), self::$distPath);
        if (self::$distPath === null) {
            $composerPath = base_path('public/build/.vite/manifest.json');
            $srcDir = dirname((new \ReflectionClass(self::class))->getFileName(), 2);
            $devPath = $srcDir . '/statics/dist';

            self::$distPath = is_dir($composerPath) ? $composerPath : $devPath;
        }

        return self::$distPath;
    }

    public static function distUrl(): string
    {
        if (self::$distUrl === null) {
            self::$distUrl = '/_framework';
        }

        return self::$distUrl;
    }

    public static function setDistUrl(string $url): void
    {
        self::$distUrl = rtrim($url, '/');
    }

    public static function vite(string $entry): string
    {
        if (self::isDev()) {
            $key = basename($entry);
            $srcPath = self::ENTRY_MAP[$key] ?? $entry;
            return 'http://localhost:5173/' . ltrim($srcPath, '/');
        }

        $manifestPath = base_path('public/build/.vite/manifest.json');
        var_dump($manifestPath);

        if (!is_file($manifestPath)) {
            return '/build/' . $entry;
        }

        if (self::$manifest === null) {
            self::$manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        }

        if (isset(self::$manifest[$entry]['file'])) {
            return '/build/' . self::$manifest[$entry]['file'];
        }

        return '/build/' . $entry;
    }

    public static function viteCss(string $entry): array
    {
        if (self::isDev()) {
            return [];
        }

        $manifestPath = base_path('public/build/.vite/manifest.json');

        if (!is_file($manifestPath)) {
            return [];
        }

        if (self::$manifest === null) {
            self::$manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        }

        $css = [];
        if (isset(self::$manifest[$entry]['css'])) {
            foreach (self::$manifest[$entry]['css'] as $file) {
                $css[] = '/build/' . $file;
            }
        }

        if (isset(self::$manifest[$entry]['imports'])) {
            foreach (self::$manifest[$entry]['imports'] as $import) {
                if (isset(self::$manifest[$import]['css'])) {
                    foreach (self::$manifest[$import]['css'] as $file) {
                        $css[] = '/build/' . $file;
                    }
                }
            }
        }

        return array_unique($css);
    }

    /**
     * Vite 入口名称 -> 开发服务器文件路径映射
     */
    private const ENTRY_MAP = [
        'ui.js'  => 'resources/js/ui.js',
        'ux.js'  => 'resources/js/ux.js',
    ];

    public static function dist(string $entry): string
    {
        if (self::isDev()) {
            $key = basename($entry);
            $srcPath = self::ENTRY_MAP[$key] ?? $entry;
            return 'http://localhost:5173/' . ltrim($srcPath, '/');
        }

        $manifestPath = self::distPath() . '/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            return self::distUrl() . '/' . ltrim($entry, '/');
        }

        if (self::$manifest === null) {
            self::$manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        }

        $key = basename($entry);
        if (isset(self::$manifest[$key]['file'])) {
            return self::distUrl() . '/' . self::$manifest[$key]['file'];
        }

        return self::distUrl() . '/' . ltrim($entry, '/');
    }

    public static function distCss(string $entry): array
    {
        if (self::isDev()) {
            return [];
        }

        $manifestPath = self::distPath() . '/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            return [];
        }

        if (self::$manifest === null) {
            self::$manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        }

        $key = basename($entry);
        $css = [];
        if (isset(self::$manifest[$key]['css'])) {
            foreach (self::$manifest[$key]['css'] as $file) {
                $css[] = self::distUrl() . '/' . $file;
            }
        }

        if (isset(self::$manifest[$key]['imports'])) {
            foreach (self::$manifest[$key]['imports'] as $import) {
                if (isset(self::$manifest[$import]['css'])) {
                    foreach (self::$manifest[$import]['css'] as $file) {
                        $css[] = self::distUrl() . '/' . $file;
                    }
                }
            }
        }

        return array_unique($css);
    }

    private static function resolveVendorPath(): string
    {
        $reflection = new \ReflectionClass(self::class);
        $classFile = $reflection->getFileName();

        if ($classFile === false) {
            return '';
        }

        $srcDir = dirname($classFile, 2);
        $vendorDir = dirname($srcDir);

        return $vendorDir;
    }
}
