<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components;

class ComponentRegistry
{
    private static array $types = [];
    private static bool $booted = false;

    public static function register(ComponentType $type): void
    {
        self::$types[$type->name()] = $type;
    }

    public static function get(string $name): ?ComponentType
    {
        self::boot();
        return self::$types[$name] ?? null;
    }

    public static function all(): array
    {
        self::boot();
        return self::$types;
    }

    public static function byCategory(): array
    {
        self::boot();
        $groups = [];
        foreach (self::$types as $type) {
            $groups[$type->category()][] = $type;
        }
        return $groups;
    }

    public static function categories(): array
    {
        return [
            'basic' => '基础',
            'layout' => '布局',
            'media' => '媒体',
        ];
    }

    private static function boot(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        self::register(new Basic\Heading());
        self::register(new Basic\TextBlock());
        self::register(new Basic\ImageBlock());
        self::register(new Basic\ButtonBlock());
        self::register(new Basic\Divider());
        self::register(new Layout\Grid());
        self::register(new Layout\Columns());
    }
}
