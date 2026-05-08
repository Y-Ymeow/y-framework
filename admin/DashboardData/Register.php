<?php

declare(strict_types=1);

namespace Admin\DashboardData;

class Register
{
    protected static array $widgets = [];

    public static function register(string $widgetClass): void
    {
        if (!is_subclass_of($widgetClass, \Framework\Component\Live\AbstractLiveComponent::class)) {
            throw new \InvalidArgumentException("{$widgetClass} must extend AbstractLiveComponent");
        }
        static::$widgets[] = $widgetClass;
    }

    public static function getWidgets(): array
    {
        return static::$widgets;
    }

    public static function boot(string $basePath): void
    {
        $dashboardDataPath = $basePath . '/admin/DashboardData';
        if (!is_dir($dashboardDataPath)) {
            return;
        }

        foreach (glob($dashboardDataPath . '/*.php') as $file) {
            if (basename($file) === 'Register.php') {
                continue;
            }
            $class = '\\Admin\\DashboardData\\' . basename($file, '.php');
            if (class_exists($class)) {
                static::register($class);
            }
        }
    }
}
