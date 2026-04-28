<?php

declare(strict_types=1);

namespace Tests\Routing;

use PHPUnit\Framework\TestCase;
use Framework\Routing\Router;
use Framework\Foundation\Application;

class RouterTest extends TestCase
{
    public function testWildcardRouteMatching()
    {
        $app = $this->createMock(Application::class);
        $router = new Router($app);
        
        $router->get('/assets/{path...}', function($path) {
            return $path;
        });
        
        $routes = $router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('/assets/{path...}', $routes[0]['path']);
    }

    public function testCallableRouteRegistration()
    {
        $app = $this->createMock(Application::class);
        $router = new Router($app);
        
        $router->get('/test', fn() => 'Hello World', 'test.route');
        
        $routes = $router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['path']);
        $this->assertEquals('test.route', $routes[0]['name']);
        $this->assertIsCallable($routes[0]['handler']);
    }

    public function testMultipleHttpMethods()
    {
        $app = $this->createMock(Application::class);
        $router = new Router($app);
        
        $router->any('/api/{endpoint...}', fn() => 'API');
        
        $routes = $router->getRoutes();
        $this->assertCount(7, $routes); // GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD
        
        $methods = array_map(fn($r) => $r['method'], $routes);
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
    }

    public function testWildcardRouteOnlyMatchesCorrectPrefix()
    {
        $app = $this->createMock(Application::class);
        $router = new Router($app);
        
        $router->get('/media/{path...}', fn($path) => 'media: ' . $path);
        $router->get('/assets/{path...}', fn($path) => 'assets: ' . $path);
        $router->get('/admin/demo', fn() => 'demo');
        
        $routes = $router->getRoutes();
        
        $matchMedia = false;
        $matchAssets = false;
        $matchAdmin = false;
        $matchWrong = false;
        
        foreach ($routes as $route) {
            $ref = new \ReflectionMethod($router, 'matchPath');
            $ref->setAccessible(true);
            
            if ($route['path'] === '/media/{path...}') {
                $result = $ref->invoke($router, $route['path'], '/media/images/test.jpg');
                $matchMedia = ($result !== false && isset($result['path']));
                
                $wrongResult = $ref->invoke($router, $route['path'], '/admin/wrong');
                if ($wrongResult !== false) $matchWrong = true;
            }
            
            if ($route['path'] === '/assets/{path...}') {
                $result = $ref->invoke($router, $route['path'], '/assets/css/style.css');
                $matchAssets = ($result !== false && isset($result['path']));
                
                $wrongResult = $ref->invoke($router, $route['path'], '/other/path');
                if ($wrongResult !== false) $matchWrong = true;
            }
            
            if ($route['path'] === '/admin/demo') {
                $result = $ref->invoke($router, $route['path'], '/admin/demo');
                $matchAdmin = ($result !== false);
                
                $wrongResult = $ref->invoke($router, $route['path'], '/admin/other');
                if ($wrongResult !== false) $matchWrong = true;
            }
        }
        
        $this->assertTrue($matchMedia, 'Should match /media/{path...}');
        $this->assertTrue($matchAssets, 'Should match /assets/{path...}');
        $this->assertTrue($matchAdmin, 'Should match /admin/demo');
        $this->assertFalse($matchWrong, 'Should not match wrong paths');
    }

    public function testWildcardCapturesAllRemainingPath()
    {
        $app = $this->createMock(Application::class);
        $router = new Router($app);
        
        $router->get('/files/{path...}', fn($path) => $path);
        
        $routes = $router->getRoutes();
        $route = $routes[0];
        
        $ref = new \ReflectionMethod($router, 'matchPath');
        $ref->setAccessible(true);
        
        $result = $ref->invoke($router, $route['path'], '/files/a/b/c/d.txt');
        $this->assertIsArray($result);
        $this->assertEquals('a/b/c/d.txt', $result['path']);
        
        $result = $ref->invoke($router, $route['path'], '/files/single.txt');
        $this->assertIsArray($result);
        $this->assertEquals('single.txt', $result['path']);
    }
}
