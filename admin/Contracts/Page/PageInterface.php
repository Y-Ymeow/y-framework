<?php

namespace Framework\Admin\Page;

interface PageInterface
{
    public static function getName(): string;
    public static function getTitle(): string;

    /**
     * 获取页面定义的路由
     * 格式: ["route.name" => ["method" => "GET", "path" => "/path", "handler" => [static::class, "method"]]]
     */
    public static function getRoutes(): array;
}