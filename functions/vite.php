<?php

declare(strict_types=1);

namespace Framework\Support;

/**
 * Vite 资源加载助手
 */
function vite(string $entry): string
{
    $debug = config('app.debug', true);
    $buildDir = 'build';

    // 1. 开发模式：指向 Vite 端口
    if ($debug) {
        return sprintf(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>' . "\n" .
            '<script type="module" src="http://localhost:5173/%s"></script>',
            ltrim($entry, '/')
        );
    }

    // 2. 生产模式：解析 manifest.json
    $manifestPath = public_path($buildDir . '/.vite/manifest.json');
    if (!is_file($manifestPath)) {
        return '';
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);
    $file = $manifest[$entry]['file'] ?? '';
    $css = $manifest[$entry]['css'] ?? [];

    $html = '';
    foreach ($css as $stylesheet) {
        $html .= sprintf('<link rel="stylesheet" href="/%s/%s">' . "\n", $buildDir, $stylesheet);
    }
    $html .= sprintf('<script type="module" src="/%s/%s"></script>', $buildDir, $file);

    return $html;
}

function public_path(string $path = ''): string
{
    return base_path('public/' . ltrim($path, '/'));
}
