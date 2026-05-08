<?php

declare(strict_types=1);

namespace Framework\Cache\Support;

final class Serialization
{
    public static function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public static function unserialize(string $data): mixed
    {
        return @unserialize($data);
    }

    public static function encodeEntry(mixed $value, ?int $expires): string
    {
        return serialize([
            'value' => $value,
            'expires' => $expires,
        ]);
    }

    public static function decodeEntry(string $data): ?array
    {
        $entry = @unserialize($data);
        if (!is_array($entry) || !array_key_exists('value', $entry)) {
            return null;
        }

        return $entry;
    }

    public static function isExpired(?int $expires): bool
    {
        return $expires !== null && $expires < time();
    }
}
