<?php

namespace Admin\Settings;

class SeoOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('seo_title', [
            'label' => ['admin.settings.seo_title', [], 'SEO 标题'],
            'type' => 'text',
            'group' => ['admin.settings.seo', [], 'SEO'],
            'default' => '',
            'description' => ['admin.settings.seo_title_desc', [], '搜索引擎显示的标题'],
        ]);

        OptionsRegistry::register('seo_description', [
            'label' => ['admin.settings.seo_description', [], 'SEO 描述'],
            'type' => 'textarea',
            'group' => ['admin.settings.seo', [], 'SEO'],
            'default' => '',
            'description' => ['admin.settings.seo_description_desc', [], '搜索引擎显示的描述文本'],
        ]);

        OptionsRegistry::register('seo_keywords', [
            'label' => ['admin.settings.seo_keywords', [], 'SEO 关键词'],
            'type' => 'text',
            'group' => ['admin.settings.seo', [], 'SEO'],
            'default' => '',
            'description' => ['admin.settings.seo_keywords_desc', [], '以逗号分隔的关键词列表'],
        ]);

        OptionsRegistry::register('seo_robots', [
            'label' => ['admin.settings.seo_robots', [], 'Robots 指令'],
            'type' => 'select',
            'group' => ['admin.settings.seo', [], 'SEO'],
            'options' => [
                'index, follow' => 'index, follow',
                'noindex, follow' => 'noindex, follow',
                'index, nofollow' => 'index, nofollow',
                'noindex, nofollow' => 'noindex, nofollow',
            ],
            'default' => 'index, follow',
            'description' => ['admin.settings.seo_robots_desc', [], '搜索引擎爬虫指令'],
        ]);

        OptionsRegistry::register('seo_og_image', [
            'label' => ['admin.settings.seo_og_image', [], 'OG 分享图'],
            'type' => 'file',
            'group' => ['admin.settings.seo', [], 'SEO'],
            'default' => '',
            'description' => ['admin.settings.seo_og_image_desc', [], '社交媒体分享时显示的图片'],
        ]);
    }
}
