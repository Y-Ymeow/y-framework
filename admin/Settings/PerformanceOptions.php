<?php

namespace Admin\Settings;

class PerformanceOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('enable_cache', [
            'label' => t('admin.settings.enable_cache'),
            'type' => 'switch',
            'group' => t('admin.settings.performance'),
            'default' => true,
            'description' => t('admin.settings.enable_cache_desc'),
        ]);

        OptionsRegistry::register('cache_ttl', [
            'label' => t('admin.settings.cache_ttl'),
            'type' => 'number',
            'group' => t('admin.settings.performance'),
            'default' => '3600',
            'description' => t('admin.settings.cache_ttl_desc'),
            'dependsOn' => ['enable_cache' => true],
        ]);

        OptionsRegistry::register('enable_minify', [
            'label' => t('admin.settings.enable_minify'),
            'type' => 'switch',
            'group' => t('admin.settings.performance'),
            'default' => false,
            'description' => t('admin.settings.enable_minify_desc'),
        ]);

        OptionsRegistry::register('enable_gzip', [
            'label' => t('admin.settings.enable_gzip'),
            'type' => 'switch',
            'group' => t('admin.settings.performance'),
            'default' => true,
            'description' => t('admin.settings.enable_gzip_desc'),
        ]);
    }
}
