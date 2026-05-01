<?php

declare(strict_types=1);

namespace Framework\Admin\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\View\Base\Element;

class DashboardPage extends LiveComponent
{
    public static function getName(): string
    {
        return 'dashboard';
    }

    public static function getTitle(): string
    {
        return t('admin.dashboard');
    }

    public function render(): string|Element
    {
        $wrapper = Element::make('div')->class('admin-dashboard', 'space-y-6');

        $header = Element::make('div')->class('flex', 'items-center', 'justify-between');
        $header->child(Element::make('h1')->class('text-2xl', 'font-bold', 'text-gray-900')->text(t('admin.dashboard')));
        $wrapper->child($header);

        $grid = Element::make('div')->class('grid', 'grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-4', 'gap-4');

        $stats = [
            ['label' => t('admin.stats.total_users'), 'value' => '0', 'icon' => 'people'],
            ['label' => t('admin.stats.today_visits'), 'value' => '0', 'icon' => 'eye'],
            ['label' => t('admin.stats.orders'), 'value' => '0', 'icon' => 'cart'],
            ['label' => t('admin.stats.revenue'), 'value' => '¥0', 'icon' => 'currency-dollar'],
        ];

        foreach ($stats as $stat) {
            $card = Element::make('div')->class('bg-white', 'rounded-lg', 'border', 'border-gray-200', 'p-6');
            $card->child(Element::make('div')->class('text-sm', 'text-gray-500')->text($stat['label']));
            $card->child(Element::make('div')->class('mt-2', 'text-3xl', 'font-bold', 'text-gray-900')->text($stat['value']));
            $grid->child($card);
        }

        $wrapper->child($grid);

        $content = Element::make('div')->class('grid', 'grid-cols-1', 'lg:grid-cols-2', 'gap-6');

        $left = Element::make('div')->class('bg-white', 'rounded-lg', 'border', 'border-gray-200', 'p-6');
        $left->child(Element::make('h2')->class('text-lg', 'font-semibold', 'mb-4')->text(t('admin.quick_start')));
        $left->child(Element::make('p')->class('text-gray-600')->text(t('admin.welcome_message')));
        $content->child($left);

        $right = Element::make('div')->class('bg-white', 'rounded-lg', 'border', 'border-gray-200', 'p-6');
        $right->child(Element::make('h2')->class('text-lg', 'font-semibold', 'mb-4')->text(t('admin.system_info')));
        $info = Element::make('dl')->class('space-y-2');

        $infoRow1 = Element::make('div')->class('flex', 'justify-between');
        $infoRow1->child(Element::make('dt')->class('text-gray-500')->text(t('admin.php_version')));
        $infoRow1->child(Element::make('dd')->class('text-gray-900')->text(PHP_VERSION));
        $info->child($infoRow1);

        $infoRow2 = Element::make('div')->class('flex', 'justify-between');
        $infoRow2->child(Element::make('dt')->class('text-gray-500')->text(t('admin.framework_version')));
        $infoRow2->child(Element::make('dd')->class('text-gray-900')->text('0.1.1'));
        $info->child($infoRow2);

        $right->child($info);
        $content->child($right);

        $wrapper->child($content);

        return $wrapper;
    }
}
