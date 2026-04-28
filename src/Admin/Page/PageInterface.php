<?php

namespace Framework\Admin\Page;

interface PageInterface
{
    public static function getName(): string;
    public static function getTitle(): string;
    public static function getRoutes(): array;
}