<?php

namespace Admin\Settings;

class PerformanceOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('enable_cache', [
            'label' => ['admin.settings.enable_cache', [], '启用缓存'],
            'type' => 'switch',
            'group' => ['admin.settings.performance', [], '性能'],
            'default' => true,
            'description' => ['admin.settings.enable_cache_desc', [], '启用页面缓存以提升性能'],
        ]);

        OptionsRegistry::register('cache_ttl', [
            'label' => ['admin.settings.cache_ttl', [], '缓存时间'],
            'type' => 'number',
            'group' => ['admin.settings.performance', [], '性能'],
            'default' => '3600',
            'description' => ['admin.settings.cache_ttl_desc', [], '缓存过期时间（秒）'],
            'dependsOn' => ['enable_cache' => true],
        ]);

        OptionsRegistry::register('enable_minify', [
            'label' => ['admin.settings.enable_minify', [], '启用压缩'],
            'type' => 'switch',
            'group' => ['admin.settings.performance', [], '性能'],
            'default' => false,
            'description' => ['admin.settings.enable_minify_desc', [], '压缩 HTML/CSS/JS 输出'],
        ]);

        OptionsRegistry::register('enable_gzip', [
            'label' => ['admin.settings.enable_gzip', [], '启用 Gzip'],
            'type' => 'switch',
            'group' => ['admin.settings.performance', [], '性能'],
            'default' => true,
            'description' => ['admin.settings.enable_gzip_desc', [], '启用 Gzip 压缩传输'],
        ]);
    }
}
