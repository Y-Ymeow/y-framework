<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Framework\Foundation\Application;
use Framework\Foundation\Kernel;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;
protected function setUp(): void
{
    parent::setUp();

    putenv('APP_KEY=test-key-for-testing');
    $_ENV['APP_KEY'] = 'test-key-for-testing';

    $this->app = new Application(dirname(__DIR__));

    // ...

        // 模拟一些环境变量
        if (!function_exists('env')) {
            function env($key, $default = null) {
                return $_ENV[$key] ?? $default;
            }
        }

        // 初始化内核并启动
        $kernel = new Kernel($this->app);
        $kernel->bootstrap();

        // 注入 Mock 配置
        if (!function_exists('config')) {
            function config($key, $default = null) {
                if ($key === 'app.key') return 'test-key-for-testing';
                return null;
            }
        }
    }
}
