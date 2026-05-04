<?php

declare(strict_types=1);

namespace Framework\Testing;

use Framework\Foundation\Application;
use Framework\Foundation\Kernel;
use Framework\Http\Request\Request;
use Framework\View\Document\AssetRegistry;

abstract class ApplicationTestCase extends \PHPUnit\Framework\TestCase
{
    protected static ?Application $app = null;

    protected ?Kernel $kernel = null;

    public static function setUpBeforeClass(): void
    {
        if (self::$app === null) {
            self::$app = new Application(dirname(__DIR__, 3));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_KEY=test-key-for-testing');
        $_ENV['APP_KEY'] = 'test-key-for-testing';

        $this->kernel = new Kernel(self::$app);
        $this->kernel->bootstrap();
    }

    protected function tearDown(): void
    {
        AssetRegistry::reset();
        \Tests\Support\TestCache::reset();
        parent::tearDown();
    }

    protected function app(): Application
    {
        return self::$app;
    }

    protected function make(string $abstract, array $params = []): mixed
    {
        return self::$app->make($abstract, $params);
    }

    protected function get(string $uri, array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $data, [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $data, [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function patch(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PATCH', $uri, $data, [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function delete(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, $data, [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function json(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        return $this->call($method, $uri, $data, [], [], $this->transformHeadersToServerVars($headers));
    }

    protected function call(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): TestResponse {
        $request = Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);

        $response = $this->kernel->handle($request);
        return new TestResponse($response);
    }

    protected function transformHeadersToServerVars(array $headers): array
    {
        $server = [];
        foreach ($headers as $key => $value) {
            $key = str_replace('-', '_', strtoupper($key));
            $server["HTTP_{$key}"] = $value;
        }
        return $server;
    }
}
