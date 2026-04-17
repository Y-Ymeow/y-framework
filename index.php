<?php

declare(strict_types=1);

/**
 * 根目录入口 (兼容无法修改 WebRoot 的云空间)
 */

// 检查是否是通过 public 访问的，如果是，建议重定向或直接引用
if (file_exists(__DIR__ . '/public/index.php') && str_contains($_SERVER['REQUEST_URI'], '/public/')) {
    require __DIR__ . '/public/index.php';
    exit;
}

require __DIR__ . '/public/index.php';
