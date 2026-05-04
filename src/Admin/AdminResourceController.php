<?php

declare(strict_types=1);

namespace Framework\Admin;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Routing\Attribute\Route;
use Framework\Admin\Live\AdminListPage;
use Framework\Admin\Live\AdminFormPage;
use Framework\Admin\Live\AdminLayout;
use Framework\Routing\Attribute\RouteGroup;
use Framework\View\Element\Container;
use Framework\View\Element\Link;
use Framework\View\Element\Listing;
use Framework\View\Element\Text;

class AdminResourceController
{
    public function dashboard(): Response
    {
        return \Admin\Pages\DashboardPage::renderPage();
    }

    public static function indexUrl(string $resource): string
    {
        return "/admin/{$resource}";
    }

    public static function createUrl(string $resource): string
    {
        return "/admin/{$resource}/create";
    }

    public static function editUrl(string $resource, mixed $id): string
    {
        return "/admin/{$resource}/{$id}/edit";
    }

    public static function recordUrl(string $resource, mixed $id): string
    {
        return "/admin/{$resource}/{$id}";
    }

    public static function deleteUrl(string $resource, mixed $id): string
    {
        return "/admin/{$resource}/{$id}/delete";
    }

    public static function customUrl(string $resource, string $action): string
    {
        return "/admin/{$resource}/{$action}";
    }

    public static function customRecordUrl(string $resource, mixed $id, string $action): string
    {
        return "/admin/{$resource}/{$id}/{$action}";
    }
}
