<?php

declare(strict_types=1);

namespace Admin\DashboardData;

use Framework\Component\Live\LiveComponent;
use Framework\UX\Display\Card;
use Framework\View\Base\Element;

class SystemInfoWidget extends LiveComponent
{
    public static function getOrder(): int { return 40; }
    public function render(): Element
    {
        $card = Card::make()
            ->title(t('admin.stats.system_info', [], '系统信息'));

        $info = [
            [t('admin.stats.php_version'), PHP_VERSION],
            [t('admin.stats.framework_version'), '1.0.0'],
            [t('admin.stats.environment'), config('app.env', 'local')],
            [t('admin.stats.debug_mode'), config('app.debug', false) ? t('admin.stats.on') : t('admin.stats.off')],
        ];

        try {
            $driver = config('database.default', 'mysql');
            $info[] = [t('admin.stats.db_driver', [], '数据库驱动'), $driver];
        } catch (\Throwable) {
        }

        $table = Element::make('table')->class('w-full', 'text-sm');
        foreach ($info as [$label, $value]) {
            $table->child(
                Element::make('tr')->class('border-b', 'border-gray-50')->children(
                    Element::make('td')->class('py-2', 'text-gray-500', 'w-1/3')->text($label),
                    Element::make('td')->class('py-2', 'text-gray-900')->text((string)$value)
                )
            );
        }

        $card->child($table);

        return Element::make('div')->class('mb-6')->child($card);
    }
}