<?php

declare(strict_types=1);

namespace Framework\Cache\Support;

use Framework\Cache\Exception\InvalidArgumentException;

final class KeyValidator
{
    private const RESERVED_CHARS = '{}()/\@:';

    public static function validate(string $key, bool $strict = false): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must not be empty.');
        }

        if ($strict && strpbrk($key, self::RESERVED_CHARS) !== false) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cache key [%s] contains reserved characters: %s',
                    $key,
                    self::RESERVED_CHARS
                )
            );
        }
    }

    public static function validateMultiple(iterable $keys, bool $strict = false): void
    {
        foreach ($keys as $key) {
            self::validate((string) $key, $strict);
        }
    }
}
