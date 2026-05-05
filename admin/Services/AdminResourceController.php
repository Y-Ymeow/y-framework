<?php

declare(strict_types=1);

namespace Admin\Services;

class AdminResourceController
{
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
