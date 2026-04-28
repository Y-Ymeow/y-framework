<?php

declare(strict_types=1);

namespace Tests\Unit\Lifecycle;

use Framework\Lifecycle\RouteCollector;

class RouteCollectorTest extends \PHPUnit\Framework\TestCase
{
    private RouteCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new RouteCollector();
    }

    public function test_collect_routes(): void
    {
        $routes = [
            ['method' => 'GET', 'path' => '/users', 'handler' => 'UserController@index'],
            ['method' => 'POST', 'path' => '/users', 'handler' => 'UserController@store'],
        ];

        $this->collector->collect($routes);
        $this->assertEquals(2, $this->collector->count());
    }

    public function test_add_route(): void
    {
        $this->collector->addRoute([
            'method' => 'GET',
            'path' => '/test',
            'handler' => 'TestController@test',
            'name' => 'test.route',
        ]);

        $this->assertEquals(1, $this->collector->count());
    }

    public function test_get_by_method(): void
    {
        $this->collector->addRoute(['method' => 'GET', 'path' => '/users', 'handler' => 'UserController@index']);
        $this->collector->addRoute(['method' => 'POST', 'path' => '/users', 'handler' => 'UserController@store']);

        $getRoutes = $this->collector->getByMethod('GET');
        $this->assertCount(1, $getRoutes);
        $this->assertEquals('GET', $getRoutes[0]['method']);
    }

    public function test_get_by_name(): void
    {
        $this->collector->addRoute([
            'method' => 'GET',
            'path' => '/users',
            'handler' => 'UserController@index',
            'name' => 'users.index',
        ]);

        $route = $this->collector->getByName('users.index');
        $this->assertNotNull($route);
        $this->assertEquals('/users', $route['path']);
    }

    public function test_get_by_group(): void
    {
        $this->collector->addRoute([
            'method' => 'GET',
            'path' => '/admin/users',
            'handler' => 'Admin\UserController@index',
            'group' => 'admin',
        ]);

        $this->collector->addRoute([
            'method' => 'GET',
            'path' => '/users',
            'handler' => 'UserController@index',
        ]);

        $adminRoutes = $this->collector->getByGroup('admin');
        $this->assertCount(1, $adminRoutes);
    }

    public function test_merge_collectors(): void
    {
        $collector2 = new RouteCollector();
        $this->collector->addRoute(['method' => 'GET', 'path' => '/route1', 'handler' => 'Test@index']);
        $collector2->addRoute(['method' => 'GET', 'path' => '/route2', 'handler' => 'Test@index']);

        $this->collector->merge($collector2);
        $this->assertEquals(2, $this->collector->count());
    }

    public function test_clear(): void
    {
        $this->collector->addRoute(['method' => 'GET', 'path' => '/test', 'handler' => 'Test@index']);
        $this->collector->clear();
        $this->assertEquals(0, $this->collector->count());
    }

    public function test_default_values(): void
    {
        $this->collector->addRoute(['method' => 'GET', 'path' => '/test', 'handler' => 'Test@index']);
        $collected = $this->collector->getCollected();

        $this->assertArrayHasKey('name', $collected[0]);
        $this->assertArrayHasKey('middleware', $collected[0]);
        $this->assertEquals('GET:/test', $collected[0]['name']);
    }
}
