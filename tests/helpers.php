<?php

declare(strict_types=1);

if (!function_exists('cache')) {
    function cache() {
        return \Tests\Support\TestCache::getInstance();
    }
}
