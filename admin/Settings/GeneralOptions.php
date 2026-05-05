<?php

namespace Admin\Settings;

class GeneralOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('site_name', [
            'label' => t('admin.settings.site_name'),
            'type' => 'text',
            'group' => t('admin.settings.general'),
            'default' => 'My Site',
            'description' => t('admin.settings.site_name_desc'),
        ]);

        OptionsRegistry::register('site_description', [
            'label' => t('admin.settings.site_description'),
            'type' => 'textarea',
            'group' => t('admin.settings.general'),
            'default' => '',
            'description' => t('admin.settings.site_description_desc'),
        ]);

        OptionsRegistry::register('site_logo', [
            'label' => t('admin.settings.site_logo'),
            'type' => 'file',
            'group' => t('admin.settings.general'),
            'default' => '',
            'description' => t('admin.settings.site_logo_desc'),
        ]);

        OptionsRegistry::register('site_favicon', [
            'label' => t('admin.settings.site_favicon'),
            'type' => 'file',
            'group' => t('admin.settings.general'),
            'default' => '',
            'description' => t('admin.settings.site_favicon_desc'),
        ]);

        OptionsRegistry::register('site_url', [
            'label' => t('admin.settings.site_url'),
            'type' => 'text',
            'group' => t('admin.settings.general'),
            'default' => '',
            'description' => t('admin.settings.site_url_desc'),
        ]);

        OptionsRegistry::register('site_timezone', [
            'label' => t('admin.settings.site_timezone'),
            'type' => 'select',
            'group' => t('admin.settings.general'),
            'options' => [
                'Asia/Shanghai' => 'Asia/Shanghai (UTC+8)',
                'Asia/Tokyo' => 'Asia/Tokyo (UTC+9)',
                'Asia/Singapore' => 'Asia/Singapore (UTC+8)',
                'America/New_York' => 'America/New_York (UTC-5)',
                'America/Los_Angeles' => 'America/Los_Angeles (UTC-8)',
                'Europe/London' => 'Europe/London (UTC+0)',
                'Europe/Paris' => 'Europe/Paris (UTC+1)',
                'UTC' => 'UTC',
            ],
            'default' => 'Asia/Shanghai',
            'description' => t('admin.settings.site_timezone_desc'),
        ]);

        OptionsRegistry::register('site_locale', [
            'label' => t('admin.settings.site_locale'),
            'type' => 'select',
            'group' => t('admin.settings.general'),
            'options' => [
                'zh' => '中文',
                'en' => 'English',
            ],
            'default' => 'zh',
            'description' => t('admin.settings.site_locale_desc'),
        ]);
    }
}
