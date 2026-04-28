<?php

declare(strict_types=1);

namespace Framework\Support;

class Arr
{
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (!is_array($array) || !array_key_exists($k, $array)) {
                return $default;
            }
            $array = $array[$k];
        }
        return $array;
    }

    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        $current = $value;
    }

    public static function has(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (!is_array($array) || !array_key_exists($k, $array)) {
                return false;
            }
            $array = $array[$k];
        }
        return true;
    }

    public static function except(array $array, array|string $keys): array
    {
        $keys = (array)$keys;
        return array_filter($array, fn($k) => !in_array($k, $keys), ARRAY_FILTER_USE_KEY);
    }

    public static function only(array $array, array|string $keys): array
    {
        $keys = (array)$keys;
        return array_filter($array, fn($k) => in_array($k, $keys), ARRAY_FILTER_USE_KEY);
    }

    public static function flatten(array $array, int $depth = INF): array
    {
        $result = [];
        foreach ($array as $item) {
            if (is_array($item) && $depth > 0) {
                $result = array_merge($result, static::flatten($item, $depth - 1));
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    public static function pluck(array $array, string $key): array
    {
        return array_map(fn($item) => is_array($item) ? ($item[$key] ?? null) : (is_object($item) ? ($item->$key ?? null) : null), $array);
    }
}
