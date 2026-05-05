<?php

declare(strict_types=1);

namespace Admin\Contracts\Page;

use Admin\Contracts\Live\AdminLayout;
use Framework\Http\Response\Response;

abstract class BasePage implements PageInterface
{
    public static function getName(): string
    {
        $class = static::class;
        $parts = explode('\\', $class);
        $className = end($parts);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Page', '', $className)));
    }

    public static function getTitle(): string
    {
        return static::getName();
    }

    public static function getRoutes(): array
    {
        $name = static::getName();
        return [
            "admin.page.{$name}" => [
                'method' => 'GET',
                'path' => "/{$name}",
                'handler' => [static::class, 'renderPage'],
            ],
        ];
    }

    /**
     * 默认的页面渲染处理器
     */
    public static function renderPage(): Response
    {
        $layout = new AdminLayout();
        $layout->activeMenu = static::getName();

        // 实例化当前页面类（假设它是一个 LiveComponent）
        $page = new static();
        if (method_exists($page, 'named')) {
            $page->named('admin-page-' . static::getName());
        }

        $layout->setContent($page);

        return Response::html($layout);
    }
}
