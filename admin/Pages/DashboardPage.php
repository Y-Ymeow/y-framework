<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\UX\Display\Card;
use Framework\UX\Display\StatCard;
use Framework\UX\Layout\Grid;
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

    public function render(): Element
    {
        $wrapper = Element::make('div')->class('admin-dashboard', 'space-y-6');

        // Header
        $header = Element::make('div')->class('flex', 'items-center', 'justify-between');
        $header->child(Element::make('h1')->class('text-2xl', 'font-bold', 'text-gray-900')->text(t('admin.dashboard')));
        $wrapper->child($header);

        // Stats Grid using StatCard components
        $statsGrid = Grid::make()
            ->cols(1)
            ->class('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-4', 'gap-4');

        $stats = [
            ['label' => t('admin.stats.total_users'), 'value' => '0', 'icon' => 'people'],
            ['label' => t('admin.stats.today_visits'), 'value' => '0', 'icon' => 'eye'],
            ['label' => t('admin.stats.orders'), 'value' => '0', 'icon' => 'cart'],
            ['label' => t('admin.stats.revenue'), 'value' => '¥0', 'icon' => 'currency-dollar'],
        ];

        foreach ($stats as $stat) {
            $statCard = StatCard::make()
                ->title($stat['label'])
                ->value($stat['value'])
                ->icon('<i class="bi bi-' . $stat['icon'] . '"></i>');
            $statsGrid->child($statCard);
        }

        $wrapper->child($statsGrid);

        // Content Grid with Cards
        $contentGrid = Grid::make()
            ->cols(1)
            ->class('grid-cols-1', 'lg:grid-cols-2', 'gap-6');

        // Quick Start Card
        $quickStartCard = Card::make()
            ->title(t('admin.quick_start'))
            ->variant('bordered')
            ->class('h-full');
        $quickStartCard->child(Element::make('p')->class('text-gray-600')->text(t('admin.welcome_message')));
        $contentGrid->child($quickStartCard);

        // System Info Card
        $systemInfoCard = Card::make()
            ->title(t('admin.system_info'))
            ->variant('bordered')
            ->class('h-full');

        $infoList = Element::make('dl')->class('space-y-2');

        $infoRow1 = Element::make('div')->class('flex', 'justify-between');
        $infoRow1->child(Element::make('dt')->class('text-gray-500')->text(t('admin.php_version')));
        $infoRow1->child(Element::make('dd')->class('text-gray-900')->text(PHP_VERSION));
        $infoList->child($infoRow1);

        $infoRow2 = Element::make('div')->class('flex', 'justify-between');
        $infoRow2->child(Element::make('dt')->class('text-gray-500')->text(t('admin.framework_version')));
        $infoRow2->child(Element::make('dd')->class('text-gray-900')->text('0.1.1'));
        $infoList->child($infoRow2);

        $systemInfoCard->child($infoList);
        $contentGrid->child($systemInfoCard);

        $wrapper->child($contentGrid);

        return $wrapper;
    }
}
