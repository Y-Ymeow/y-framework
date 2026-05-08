<?php

declare(strict_types=1);

namespace App\Providers;

use Admin\PageBuilder\PageBuilderPageModel;
use Framework\Foundation\Application;
use Framework\Foundation\ServiceProvider;
use Framework\Http\Response\Response;
use Framework\Routing\Router;
use Framework\View\Base\Element;

class PageBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $router = $this->app->make(Router::class);
            $rows = PageBuilderPageModel::all();

            foreach ($rows as $row) {
                $item = ($row instanceof \Framework\Support\Collection) ? $row->toArray() : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
                $className = $item['name'] ?? '';
                $route = $item['route'] ?? '';

                if (empty($className) || empty($route)) continue;

                $router->addRoute('GET', $route, function () use ($className) {
                    $renderer = new \App\Service\PageRenderer();
                    $response = $renderer->render($className);
                    if ($response) {
                        return $response;
                    }
                    return Response::html(
                        Element::make('div')->class('pb-page')->text('页面为空')
                    );
                }, 'page.' . strtolower($className));
            }
        } catch (\Throwable $e) {
            try {
                logger()->error('[PageBuilderServiceProvider] boot failed', ['error' => $e->getMessage()]);
            } catch (\Throwable) {}
        }
    }
}
