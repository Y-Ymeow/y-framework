<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\Middleware;
use Framework\Http\Middleware\AdminAuthenticate;
use Admin\DashboardData\Register;
use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Framework\Component\Live\LiveComponent;
use Framework\View\Base\Element;
use Framework\UX\Layout\Grid;
use Framework\UX\Feedback\EmptyState;
use Framework\UX\UXComponent;

class DashboardPage implements PageInterface
{
    public static function getRoutes(): array
    {
        return [
            new Route('/admin', ['GET'], AdminAuthenticate::class),
        ];
    }

    public static function getName(): string
    {
        return 'dashboard';
    }

    public static function getTitle(): string|array
    {
        return ['admin.dashboard', [], '控制台'];
    }

    public static function getIcon(): string
    {
        return 'speedometer2';
    }

    public static function getGroup(): string
    {
        return '';
    }

    public static function getSort(): int
    {
        return 0;
    }

    #[Route(path: '/admin', methods: ['GET'])]
    #[Middleware(AdminAuthenticate::class)]
    public function __invoke(): LiveComponent
    {
        $layout = new AdminLayout();
        $layout->setContent($this->renderDashboard());
        return $layout;
    }

    protected function renderDashboard(): mixed
    {
        $widgets = Register::getWidgets();

        if (empty($widgets)) {
            return $this->renderEmptyDashboard();
        }

        $statWidgets = [];
        $otherWidgets = [];

        foreach ($widgets as $widgetClass) {
            $section = method_exists($widgetClass, 'getSection') ? $widgetClass::getSection() : 'content';
            if ($section === 'stats') {
                $statWidgets[] = $widgetClass;
            } else {
                $otherWidgets[] = $widgetClass;
            }
        }

        $wrapper = Element::make('div')->class('dashboard-widgets', 'space-y-6');

        if (!empty($statWidgets)) {
            $statRow = Element::make('div')->class('grid', 'grid-cols-4', 'gap-6');
            foreach ($statWidgets as $widgetClass) {
                $widget = new $widgetClass();
                $statRow->child($widget->toHtml());
            }
            $wrapper->child($statRow);
        }

        if (!empty($otherWidgets)) {
            $grid = Grid::make()->cols(2)->gap(6);
            foreach ($otherWidgets as $widgetClass) {
                $widget = new $widgetClass();
                $grid->child($widget->toHtml());
            }
            $wrapper->child($grid);
        }

        return $wrapper;
    }

    protected function renderEmptyDashboard(): UXComponent
    {
        return EmptyState::make()
            ->description(t('admin.no_widgets_registered'));
    }
}
