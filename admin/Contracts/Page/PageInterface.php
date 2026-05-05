<?php

namespace Admin\Contracts\Page;

interface PageInterface
{
    public static function getName(): string;
    public static function getTitle(): string;

    public static function getIcon(): string;

    public static function getGroup(): string;

    public static function getSort(): int;

    /**
     * 获取页面定义的路由
     * 格式: ["route.name" => ["method" => "GET", "path" => "/path", "handler" => [static::class, "method"]]]
     */
    public static function getRoutes(): array;
}