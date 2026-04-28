<?php

declare(strict_types=1);

namespace Framework\Support;

class Asset
{
    private static ?array $manifest = null;
    private static ?bool $isDev = null;

    public static function isDev(): bool
    {
        if (self::$isDev !== null) {
            return self::$isDev;
        }

        // 尝试连接 Vite Dev Server
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 0.1);
        if ($socket) {
            fclose($socket);
            return self::$isDev = true;
        }

        return self::$isDev = false;
    }

    public static function vite(string $entry): string
    {
        if (self::isDev()) {
            return 'http://localhost:5173/' . ltrim($entry, '/');
        }

        $manifestPath = base_path('public/build/.vite/manifest.json');

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
            return []; // 开发模式下，CSS 通过 JS 自动注入，无需手动加载
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

        // 处理引入的组件中的 CSS
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
}
