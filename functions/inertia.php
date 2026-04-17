<?php

declare(strict_types=1);

namespace Framework\Support;

use Framework\Http\Request;
use Framework\Http\Response;

/**
 * 仿 Inertia 风格的 SPA 渲染助手
 */
function inertia(string $component, array $props = []): Response
{
    $request = app(Request::class);
    
    // 获取当前版本，用于强制刷新 (可由 Vite manifest 算出)
    $version = config('app.version', '1.0');

    $page = [
        'component' => $component,
        'props' => $props,
        'url' => $request->uri(),
        'version' => $version,
    ];

    // 如果是来自 React 的 XHR 请求
    if ($request->header('X-Inertia')) {
        return Response::json($page, 200, [
            'X-Inertia' => 'true',
            'Vary' => 'Accept',
        ]);
    }

    // 否则返回 HTML 壳组件
    return new Response(InertiaShell($page));
}

/**
 * SPA 的 HTML 壳
 */
function InertiaShell(array $page): string
{
    use function Framework\UI\{Document, div, script};
    
    return Document($page['component'], [
        // 加载 Vite 资源
        \Framework\Support\vite('resources/js/app.tsx')
    ], [
        // 渲染入口 Div
        div([
            'id' => 'app', 
            'data-page' => json_encode($page)
        ])
    ]);
}
