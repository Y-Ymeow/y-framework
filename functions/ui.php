<?php

declare(strict_types=1);

namespace Framework\UI;

/**
 * 核心 Hyper 函数：生成 HTML 字符串
 */
function h(string $tag, array $props = [], ...$children): string
{
    $attrs = '';
    foreach ($props as $key => $value) {
        if (is_bool($value)) {
            if ($value) $attrs .= " {$key}";
        } else {
            $attrs .= sprintf(' %s="%s"', $key, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
        }
    }

    $content = '';
    foreach ($children as $child) {
        $content .= is_array($child) ? implode('', $child) : (string)$child;
    }

    // 处理自闭合标签
    if (in_array($tag, ['img', 'input', 'br', 'hr', 'meta', 'link'])) {
        return "<{$tag}{$attrs}>";
    }

    return "<{$tag}{$attrs}>{$content}</{$tag}>";
}

// 常用标签快捷函数
function div(array $props = [], ...$children) { return h('div', $props, ...$children); }
function span(array $props = [], ...$children) { return h('span', $props, ...$children); }
function h1(array $props = [], ...$children) { return h('h1', $props, ...$children); }
function ul(array $props = [], ...$children) { return h('ul', $props, ...$children); }
function li(array $props = [], ...$children) { return h('li', $props, ...$children); }
function a(array $props = [], ...$children) { return h('a', $props, ...$children); }
function p(array $props = [], ...$children) { return h('p', $props, ...$children); }
function html(array $props = [], ...$children) { return h('html', $props, ...$children); }
function head(...$children) { return h('head', [], ...$children); }
function body(array $props = [], ...$children) { return h('body', $props, ...$children); }
function title($text) { return h('title', [], $text); }
function meta(array $props) { return h('meta', $props); }
function link(array $props) { return h('link', $props); }
function script(array $props, $content = '') { return h('script', $props, $content); }
function input(array $props) { return h('input', $props); }
function form(array $props = [], ...$children) { 
    // 自动为非 GET 表单添加 CSRF
    $method = strtoupper($props['method'] ?? 'GET');
    if ($method !== 'GET') {
        array_unshift($children, CsrfField());
    }
    return h('form', $props, ...$children); 
}

/**
 * 生成 CSRF 隐藏域
 */
function CsrfField(): string
{
    return input([
        'type' => 'hidden',
        'name' => '_token',
        'value' => app(\Framework\Http\Session::class)->token()
    ]);
}

/**
 * 静态资源加载助手
 */
function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

/**
 * 全局动态组件注册中心
 */
final class LiveRegistry
{
    private static array $components = [];

    public static function register(string $id, callable $renderer): void
    {
        self::$components[$id] = $renderer;
    }

    public static function get(string $id): ?callable
    {
        return self::$components[$id] ?? null;
    }
}

/**
 * 高效动态组件容器 (支持精准 ID 更新)
 */
function LiveComponent(string $id, callable $renderer, array $props = []): string
{
    // 1. 注册组件
    LiveRegistry::register($id, $renderer);

    // 2. 如果是针对该 ID 的 Y-Live 请求，直接返回渲染结果
    $request = app(\Framework\Http\Request::class);
    if ($request->header('X-Live-Id') === $id) {
        // 在 Kernel 捕获处处理，或者在这里抛出中断
    }

    return div([
        'data-live-id' => $id, 
        'data-props' => json_encode($props),
        'class' => 'y-live-component'
    ], 
        $renderer($props)
    );
}
function Document(string $title, array $head, array $bodyContent): string
{
    return "<!DOCTYPE html>\n" . 
        html(['lang' => 'zh-CN'],
            head(
                meta(['charset' => 'UTF-8']),
                meta(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0']),
                title($title),
                ...$head
            ),
            body([], ...$bodyContent)
        );
}

/**
 * 缓存 UI 片段
 */
function cache_ui(string $key, callable $callback, int $ttl = 3600): string
{
    $cache = app(\Framework\Cache\FileCache::class);
    $cacheKey = "ui_cache_" . md5($key);
    
    // 简单模拟缓存读取
    $path = $cache->path($cacheKey);
    if (file_exists($path) && (time() - filemtime($path) < $ttl)) {
        return file_get_contents($path);
    }

    $content = $callback();
    $cache->write($cacheKey, $content);
    return $content;
}
