<?php

namespace Admin\Settings;

class SeoOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('seo_title', [
            'label' => t('admin.settings.seo_title'),
            'type' => 'text',
            'group' => t('admin.settings.seo'),
            'default' => '',
            'description' => t('admin.settings.seo_title_desc'),
        ]);

        OptionsRegistry::register('seo_description', [
            'label' => t('admin.settings.seo_description'),
            'type' => 'textarea',
            'group' => t('admin.settings.seo'),
            'default' => '',
            'description' => t('admin.settings.seo_description_desc'),
        ]);

        OptionsRegistry::register('seo_keywords', [
            'label' => t('admin.settings.seo_keywords'),
            'type' => 'text',
            'group' => t('admin.settings.seo'),
            'default' => '',
            'description' => t('admin.settings.seo_keywords_desc'),
        ]);

        OptionsRegistry::register('seo_robots', [
            'label' => t('admin.settings.seo_robots'),
            'type' => 'select',
            'group' => t('admin.settings.seo'),
            'options' => [
                'index, follow' => 'index, follow',
                'noindex, follow' => 'noindex, follow',
                'index, nofollow' => 'index, nofollow',
                'noindex, nofollow' => 'noindex, nofollow',
            ],
            'default' => 'index, follow',
            'description' => t('admin.settings.seo_robots_desc'),
        ]);

        OptionsRegistry::register('seo_og_image', [
            'label' => t('admin.settings.seo_og_image'),
            'type' => 'file',
            'group' => t('admin.settings.seo'),
            'default' => '',
            'description' => t('admin.settings.seo_og_image_desc'),
        ]);
    }
}
