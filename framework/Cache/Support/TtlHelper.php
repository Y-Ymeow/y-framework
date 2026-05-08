<?php

declare(strict_types=1);

namespace Framework\Cache\Support;

final class TtlHelper
{
    public static function resolveTtl(\DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            return $now->add($ttl)->getTimestamp();
        }

        return time() + $ttl;
    }

    public static function resolveTtlSeconds(\DateInterval|int|null $ttl, int $default = 3600): int
    {
        if ($ttl === null) {
            return $default;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            return $now->add($ttl)->getTimestamp() - time();
        }

        return $ttl;
    }
}
