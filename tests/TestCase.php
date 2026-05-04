<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Framework\Foundation\Application;
use Framework\Foundation\Kernel;
use Framework\View\Document\AssetRegistry;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_KEY=test-key-for-testing');
        $_ENV['APP_KEY'] = 'test-key-for-testing';

        $this->app = new Application(dirname(__DIR__));

        if (!function_exists('env')) {
            function env($key, $default = null) {
                return $_ENV[$key] ?? $default;
            }
        }

        $kernel = new Kernel($this->app);
        $kernel->bootstrap();
    }

    protected function tearDown(): void
    {
        AssetRegistry::reset();
        \Tests\Support\TestCache::reset();
        parent::tearDown();
    }

    protected function assertHtmlContains(string $html, string $needle): void
    {
        $this->assertStringContainsString($needle, $html);
    }

    protected function assertHtmlNotContains(string $html, string $needle): void
    {
        $this->assertStringNotContainsString($needle, $html);
    }

    protected function assertSelectorExists(string $html, string $selector): void
    {
        \Tests\Support\DOMAssert::assertSelectorExists($html, $selector);
    }

    protected function assertSelectorCount(string $html, string $selector, int $count): void
    {
        \Tests\Support\DOMAssert::assertSelectorCount($html, $selector, $count);
    }

    protected function assertDataAttribute(string $html, string $attr, string $value): void
    {
        \Tests\Support\DOMAssert::assertDataAttributeEquals($html, '', $attr, $value);
    }
}
