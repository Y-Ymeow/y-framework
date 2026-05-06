<?php

namespace Admin\Settings;

class GeneralOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('site_name', [
            'label' => ['admin.settings.site_name', [], '站点名称'],
            'type' => 'text',
            'group' => ['admin.settings.general', [], '常规'],
            'default' => 'My Site',
            'description' => ['admin.settings.site_name_desc', [], '网站的名称，显示在浏览器标题栏'],
        ]);

        OptionsRegistry::register('site_description', [
            'label' => ['admin.settings.site_description', [], '站点描述'],
            'type' => 'textarea',
            'group' => ['admin.settings.general', [], '常规'],
            'default' => '',
            'description' => ['admin.settings.site_description_desc', [], '网站的简短描述'],
        ]);

        OptionsRegistry::register('site_logo', [
            'label' => ['admin.settings.site_logo', [], '站点 Logo'],
            'type' => 'file',
            'group' => ['admin.settings.general', [], '常规'],
            'default' => '',
            'description' => ['admin.settings.site_logo_desc', [], '上传网站 Logo 图片'],
        ]);

        OptionsRegistry::register('site_favicon', [
            'label' => ['admin.settings.site_favicon', [], '站点图标'],
            'type' => 'file',
            'group' => ['admin.settings.general', [], '常规'],
            'default' => '',
            'description' => ['admin.settings.site_favicon_desc', [], '浏览器标签页图标'],
        ]);

        OptionsRegistry::register('site_url', [
            'label' => ['admin.settings.site_url', [], '站点 URL'],
            'type' => 'text',
            'group' => ['admin.settings.general', [], '常规'],
            'default' => '',
            'description' => ['admin.settings.site_url_desc', [], '网站的完整 URL 地址'],
        ]);

        OptionsRegistry::register('site_timezone', [
            'label' => ['admin.settings.site_timezone', [], '时区'],
            'type' => 'select',
            'group' => ['admin.settings.general', [], '常规'],
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
            'description' => ['admin.settings.site_timezone_desc', [], '选择网站使用的时区'],
        ]);

        OptionsRegistry::register('site_locale', [
            'label' => ['admin.settings.site_locale', [], '默认语言'],
            'type' => 'select',
            'group' => ['admin.settings.general', [], '常规'],
            'options' => [
                'zh' => '中文',
                'en' => 'English',
            ],
            'default' => 'zh',
            'description' => ['admin.settings.site_locale_desc', [], '网站的默认语言'],
        ]);
    }
}
