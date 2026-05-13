<?php

declare(strict_types=1);

namespace Shop;

use Framework\Plugin\PluginManager;
use Admin\Services\AdminManager;

class Plugin extends \Framework\Plugin\Plugin
{
    public function getName(): string
    {
        return 'shop';
    }

    public function getTitle(): string
    {
        return '商城';
    }

    public function getDescription(): string
    {
        return '电商商城模块，提供商品管理、订单管理、分类管理等功能';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function boot(): void
    {
        $manager = app(PluginManager::class);
        $manager->autoloadPlugin('shop', 'Shop', __DIR__);

        AdminManager::registerResource(\Shop\Resources\ProductResource::class);
        AdminManager::registerResource(\Shop\Resources\CategoryResource::class);
        AdminManager::registerResource(\Shop\Resources\OrderResource::class);
    }
}