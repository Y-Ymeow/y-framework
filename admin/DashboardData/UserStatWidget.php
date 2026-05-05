<?php

declare(strict_types=1);

namespace Admin\DashboardData;

use Framework\Component\Live\LiveComponent;
use Framework\UX\Display\StatCard;
use Framework\View\Base\Element;

class UserStatWidget extends LiveComponent
{
    public function render(): Element
    {
        $userModel = \Admin\Auth\User::class;
        try {
            $count = $userModel::query()->count();
        } catch (\Throwable) {
            $count = 0;
        }

        $statCard = StatCard::make()
            ->title(t('admin.stats.total_users'))
            ->value((string)$count)
            ->icon('bi-people')
            ->variant('primary');

        $wrapper = Element::make('div');
        $wrapper->child($statCard);
        return $wrapper;
    }
}
