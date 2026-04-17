<?php

declare(strict_types=1);

/**
 * FrankenPHP Worker 模式入口
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// 1. 在循环外初始化应用 (单次加载，常驻内存)
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(\Framework\Core\Kernel::class);

// 2. 启动 Worker 循环
$handler = static function () use ($kernel) {
    // 捕获当前请求 (FrankenPHP 会在循环中更新超全局变量)
    $request = \Framework\Http\Request::capture();
    
    // 处理请求并获取响应
    $response = $kernel->handle($request);
    
    // 发送响应
    $response->send();
};

// frankenphp_handle_request 会在每次请求时调用 $handler
// 它会自动管理内存和清理状态
for ($nbRequests = 0, $running = true; $running && \frankenphp_handle_request($handler); ++$nbRequests) {
    // 这里可以根据需要进行每请求一次的清理逻辑
    // 比如：清理数据库连接的空闲状态等
}
