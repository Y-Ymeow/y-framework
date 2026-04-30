<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Admin\Page\BasePage;
use Framework\Admin\AdminManager;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Text;
use Framework\UX\UI\StatCard;
use Framework\UX\Layout\Grid;

class DashboardPage extends BasePage
{
    public static function getTitle(): string
    {
        return '控制台';
    }

    public static function getName(): string
    {
        return 'dashboard';
    }

    public function render(): string|Element
    {
        return Container::make()->class('p-6')->children(
            Text::h1('欢迎回来，管理员')->class('text-2xl font-bold mb-6'),

            Grid::make()->cols(4)->gap(4)->class('mb-8')->children(
                StatCard::make()->title('资源总数')->value((string)count(AdminManager::getResources()))->icon('📦')->description('已注册的后台资源'),
                StatCard::make()->title('页面总数')->value((string)count(AdminManager::getPages()))->icon('📄')->description('自定义后台页面'),
                StatCard::make()->title('系统状态')->value('运行中')->icon('⚡')->trendUp('100%'),
                StatCard::make()->title('今日访问')->value('1,240')->icon('👥')->trendUp('12%')
            ),

            Container::make()->class('bg-white p-8 rounded-xl border border-gray-200 shadow-sm')
                ->child(Text::h2('开始管理')->class('text-lg font-semibold mb-4'))
                ->child(Text::p('请从左侧菜单选择要管理的资源。您可以在 Resources 目录下定义更多资源，或者在 Pages 目录下定义更多自定义后台页面。'))
        );
    }
}
