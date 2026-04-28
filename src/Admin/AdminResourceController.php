<?php

declare(strict_types=1);

namespace Framework\Admin;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Post;
use Framework\Routing\Attribute\Put;
use Framework\Routing\Attribute\Delete;
use Framework\Routing\Attribute\Route;
use Framework\Admin\Live\AdminListPage;
use Framework\Admin\Live\AdminFormPage;
use Framework\Admin\Live\AdminLayout;
use Framework\View\Container;
use Framework\View\Link;
use Framework\View\Listing;
use Framework\View\Text;

#[Route(prefix: '/admin')]
class AdminResourceController
{
    #[Get('/')]
    public function dashboard(): Response
    {
        $resources = AdminManager::getResources();
        $pages = AdminManager::getPages();

        $el = Container::make()
            ->class('admin-dashboard')
            ->child(Text::h1(AdminManager::getBrandTitle()));

        $html = '<div class="admin-dashboard">';
        $html .= '<h1>' . AdminManager::getBrandTitle() . '</h1>';

        if (!empty($resources)) {
            $el->child(Text::h2('资源管理'));
            $el->child(Listing::ul());

            foreach ($resources as $resourceClass) {
                $name = $resourceClass::getName();
                $title = $resourceClass::getTitle();
                $el->child(
                    Listing::li()
                    ->child(Link::make()
                    ->href("/admin/{$name}")
                    ->text($title)
                    )
                );
            }
        }

        if (!empty($pages)) {
            $el->child(Text::h2('页面'));
            $el->child(Listing::ul());
            foreach ($pages as $pageClass) {
                $name = $pageClass::getName();
                $title = $pageClass::getTitle();
                $el->child(
                    Listing::li()
                    ->child(Link::make()
                    ->href("/admin/{$name}")
                    ->text($title)
                    )
                );
            }
        }

        return Response::html($el);
    }

    #[Get('/{resource}')]
    public function index(string $resource): Response
    {
        $resourceClass = AdminManager::getResource($resource);
        if (!$resourceClass) {
            return Response::html('Resource not found', 404);
        }

        $layout = new AdminLayout();
        $layout->activeMenu = $resource;
        
        $listPage = new AdminListPage();
        $listPage->named("admin-list-{$resource}");
        $listPage->resourceName = $resource;
        
        $layout->content = $listPage;

        return Response::html($layout);
    }

    #[Get('/{resource}/create')]
    public function create(string $resource): Response
    {
        $resourceClass = AdminManager::getResource($resource);
        if (!$resourceClass) {
            return Response::html('Resource not found', 404);
        }

        $layout = new AdminLayout();
        $layout->activeMenu = $resource;
        
        $formPage = new AdminFormPage();
        $formPage->named("admin-form-{$resource}-create");
        $formPage->resourceName = $resource;
        
        $layout->content = $formPage;

        return Response::html($layout);
    }

    #[Get('/{resource}/{id}/edit')]
    public function edit(string $resource, int $id): Response
    {
        $resourceClass = AdminManager::getResource($resource);
        if (!$resourceClass) {
            return Response::html('Resource not found', 404);
        }

        $layout = new AdminLayout();
        $layout->activeMenu = $resource;
        
        $formPage = new AdminFormPage();
        $formPage->named("admin-form-{$resource}-{$id}");
        $formPage->resourceName = $resource;
        $formPage->recordId = $id;
        
        $layout->content = $formPage;

        return Response::html($layout);
    }

    public static function indexUrl(string $resource): string
    {
        return "/admin/{$resource}";
    }

    public static function createUrl(string $resource, mixed $id): string
    {
        return "/admin/{$resource}/{$id}/create";
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
