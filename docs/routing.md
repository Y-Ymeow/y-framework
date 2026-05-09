# 路由系统文档

> 框架的路由系统：定义、匹配、分组、中间件、属性路由、缓存全流程。

---

## 目录

1. [架构概览](#1-架构概览)
2. [路由定义方式](#2-路由定义方式)
3. [路由参数](#3-路由参数)
4. [路由分组](#4-路由分组)
5. [属性路由（Attribute）](#5-属性路由attribute)
6. [中间件系统](#6-中间件系统)
7. [路由匹配与分发](#7-路由匹配与分发)
8. [路由缓存](#8-路由缓存)
9. [Handler 类型与响应归一化](#9-handler-类型与响应归一化)
10. [完整 API 速查](#10-完整-api-速查)

---

## 1. 架构概览

```
Router (核心调度器)
  ├── RouteCollection          路由集合（按 method/name 索引）
  │   └── Route                单条路由（路径编译、参数匹配、URL 生成）
  │
  ├── MiddlewareManager        中间件管理器（全局/分组/别名/管道）
  │
  ├── Attribute\Route          路由属性（方法级/类级）
  ├── Attribute\RouteGroup     路由组属性（类级前缀/中间件）
  ├── Attribute\Middleware     中间件属性（优先级/范围限制）
  │
  └── scan()                   自动扫描目录注册属性路由
```

### 请求处理流程

```
HTTP Request
  │
  ├── Router::dispatch($request)
  │   ├── 遍历 RouteCollection::getByMethod($method)
  │   ├── Route::match($path) → 参数提取
  │   └── Router::invokeWithMiddleware()
  │       ├── MiddlewareManager::pipe() → 中间件管道
  │       │   ├── 全局中间件
  │       │   ├── 分组中间件
  │       │   └── 路由中间件
  │       └── Router::invoke() → 执行 Handler
  │           ├── Closure → invokeCallable()
  │           ├── [Class, Method] → 实例化 + 反射调用
  │           │   ├── LiveComponent → 特殊处理
  │           │   └── 普通类 → resolveArguments()
  │           └── normalizeResponse() → Response
  │
  └── Response
```

---

## 2. 路由定义方式

### 2.1 手动注册

```php
$router = app(Router::class);

// 基础
$router->addRoute('GET', '/users', [UserController::class, 'index'], 'users.index');

// 快捷方法
$router->get('/users', [UserController::class, 'index'], 'users.index');
$router->post('/users', [UserController::class, 'store'], 'users.store');
$router->put('/users/{id}', [UserController::class, 'update'], 'users.update');
$router->patch('/users/{id}', [UserController::class, 'patch'], 'users.patch');
$router->delete('/users/{id}', [UserController::class, 'destroy'], 'users.destroy');
$router->options('/users', [UserController::class, 'options'], 'users.options');

// 多方法
$router->match(['GET', 'POST'], '/contact', [ContactController::class, 'handle'], 'contact');

// 全方法
$router->any('/catch-all', $handler, 'catch-all');
```

### 2.2 Handler 类型

| Handler 类型 | 示例 | 说明 |
|-------------|------|------|
| 闭包 | `fn() => 'Hello'` | 简单逻辑 |
| 数组 | `[Controller::class, 'method']` | 控制器方法 |
| 数组（实例） | `[$controller, 'method']` | 已有实例 |
| 可调用对象 | `$invokableObject` | 实现 `__invoke()` |

### 2.3 路由命名

```php
$router->get('/users', [UserController::class, 'index'], 'users.index');

// 通过名称查找路由
$route = $router->getRouteByName('users.index');
```

---

## 3. 路由参数

### 3.1 基础参数

```php
$router->get('/users/{id}', function ($id) {
    return "User: {$id}";
});

$router->get('/posts/{postId}/comments/{commentId}', function ($postId, $commentId) {
    return "Post: {$postId}, Comment: {$commentId}";
});
```

### 3.2 参数约束

```php
$route = $router->get('/users/{id}', [UserController::class, 'show'], 'users.show');
$route->where('id', '[0-9]+');  // 只匹配数字

// 多个约束
$route->where([
    'id' => '[0-9]+',
    'slug' => '[a-z0-9-]+',
]);
```

### 3.3 可选参数与默认值

```php
$route = $router->get('/posts/{category?}', [PostController::class, 'list'], 'posts.list');
$route->defaults(['category' => 'all']);
```

### 3.4 通配参数

```php
// {name:...} 匹配剩余所有路径段
$router->get('/files/{path...}', [FileController::class, 'show'], 'files.show');
// /files/a/b/c.txt → path = 'a/b/c.txt'
```

### 3.5 URL 生成

```php
$route = $router->getRouteByName('users.show');
$url = $route->generateUrl(['id' => 42]);
// → /users/42

// 额外参数变为查询字符串
$url = $route->generateUrl(['id' => 42, 'page' => 2]);
// → /users/42?page=2
```

### 3.6 参数解析（反射注入）

```php
// 路由参数自动按名称注入
$router->get('/users/{id}', function (int $id) {
    return User::find($id);
});

// Request 自动注入
$router->post('/users', function (Request $request) {
    return User::create($request->all());
});

// 混合使用
$router->put('/users/{id}', function (Request $request, $id) {
    // $request 自动注入，$id 从路径提取
});
```

**解析规则**：
1. 类型为 `Request` 的参数 → 注入当前请求
2. 名称匹配路由参数 → 注入路径值
3. 有默认值 → 使用默认值
4. 允许 NULL → 注入 null
5. 其他 → 注入 null

---

## 4. 路由分组

### 4.1 基础分组

```php
$router->group([
    'prefix' => '/admin',
    'middleware' => [AdminAuthenticate::class],
    'name' => 'admin',
], function (Router $router) {
    $router->get('/', [DashboardController::class, 'index'], 'dashboard');
    // → GET /admin, name: admin.dashboard, middleware: [AdminAuthenticate]

    $router->get('/users', [UserController::class, 'index'], 'users');
    // → GET /admin/users, name: admin.users, middleware: [AdminAuthenticate]
});
```

### 4.2 嵌套分组

```php
$router->group(['prefix' => '/admin', 'name' => 'admin'], function (Router $router) {
    $router->group(['prefix' => '/users', 'name' => 'users'], function (Router $router) {
        $router->get('/', [UserController::class, 'index'], 'index');
        // → GET /admin/users, name: admin.users.index
    });
});
```

### 4.3 分组属性

| 属性 | 说明 | 效果 |
|------|------|------|
| `prefix` | URL 前缀 | 所有路由路径加前缀 |
| `middleware` | 中间件列表 | 所有路由应用中间件 |
| `name` | 名称前缀 | 路由名加前缀（用 `.` 连接） |

---

## 5. 属性路由（Attribute）

### 5.1 #[Route] — 方法定义路由

```php
use Framework\Routing\Attribute\Route;

class UserController
{
    #[Route(path: '/users', methods: ['GET'], name: 'users.index')]
    public function index(): Response
    {
        return Response::json(User::all());
    }

    #[Route(path: '/users/{id}', methods: ['GET'], name: 'users.show')]
    public function show($id): Response
    {
        return Response::json(User::find($id));
    }

    #[Route(path: '/users', methods: ['POST'], name: 'users.store')]
    public function store(Request $request): Response
    {
        return Response::json(User::create($request->all()));
    }
}
```

### 5.2 #[Route] — 类定义路由（__invoke）

```php
#[Route(path: '/dashboard', methods: ['GET'], name: 'dashboard')]
class DashboardController
{
    public function __invoke(): Response
    {
        return Response::html('Dashboard');
    }
}
```

**LiveComponent 特殊处理**：如果类是 `LiveComponent` 子类，handler 自动使用 `toHtml` 方法：

```php
#[Route(path: '/admin/login', methods: ['GET'])]
class LoginPage extends LiveComponent
{
    // handler 自动映射为 [LoginPage::class, 'toHtml']
}
```

### 5.3 #[RouteGroup] — 类级前缀

```php
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: '/api/v1', middleware: [ApiAuth::class], name: 'api.v1')]
class ApiV1Controller
{
    #[Route(path: '/users', methods: ['GET'])]
    public function listUsers(): Response { ... }
    // → GET /api/v1/users, name: api.v1.listUsers, middleware: [ApiAuth]
}
```

### 5.4 #[Middleware] — 中间件属性

```php
use Framework\Routing\Attribute\Middleware;

// 类级中间件
#[Middleware(AuthMiddleware::class)]
class DashboardController
{
    // 所有方法都需要认证
}

// 带参数
#[Middleware(ThrottleMiddleware::class, params: ['max' => 60, 'decay' => 60])]
class ApiController { ... }

// 指定优先级（数字越小越先执行）
#[Middleware(LogMiddleware::class, priority: -10)]
class DebugController { ... }

// 限制应用范围
#[Middleware(AuthMiddleware::class, only: ['store', 'update', 'destroy'])]
class PostController
{
    public function index() { ... }    // 无中间件
    public function store() { ... }    // 有 AuthMiddleware
}

// 排除某些方法
#[Middleware(AuthMiddleware::class, except: ['index', 'show'])]
class ProductController
{
    public function index() { ... }    // 无中间件
    public function show($id) { ... }  // 无中间件
    public function store() { ... }    // 有 AuthMiddleware
}

// 多个中间件
#[Middleware([AuthMiddleware::class, RoleMiddleware::class])]
class AdminController { ... }

// 方法级中间件
class PostController
{
    #[Route(path: '/posts', methods: ['POST'])]
    #[Middleware(AuthMiddleware::class)]
    public function store() { ... }
}
```

### 5.5 #[Middleware] 参数说明

| 参数 | 类型 | 说明 |
|------|------|------|
| `middleware` | `string\|array` | 中间件类名 |
| `priority` | `int` | 优先级（默认 0，越小越先执行） |
| `params` | `array` | 传递给 `handle()` 的额外参数 |
| `only` | `array` | 仅应用到指定方法 |
| `except` | `array` | 排除指定方法 |

### 5.6 自动扫描注册

```php
$router->scan([
    '/app/Controllers',
    '/app/Pages',
]);
```

扫描流程：
1. 遍历目录下所有 `.php` 文件
2. 反射获取类信息
3. 读取 `#[RouteGroup]` → 提取 prefix/middleware/name
4. 读取类级 `#[Route]` → 注册类路由
5. 读取方法级 `#[Route]` → 注册方法路由
6. 读取 `#[Middleware]` → 合并中间件

---

## 6. 中间件系统

### 6.1 MiddlewareManager

```php
$mm = $router->getMiddlewareManager();
```

### 6.2 全局中间件

```php
$mm->use(TrimStrings::class);
$mm->use([TrimStrings::class, ConvertEmptyStringsToNull::class]);

// 带优先级
$mm->use(LogMiddleware::class, priority: -10);
```

### 6.3 分组中间件

```php
$mm->group('admin', AdminAuthenticate::class);
$mm->group('api', [ApiAuth::class, ThrottleMiddleware::class]);
```

### 6.4 别名

```php
$mm->alias('auth', Authenticate::class);
$mm->alias('admin', AdminAuthenticate::class);

// 使用别名
$router->get('/admin', $handler)->middleware('auth', 'admin');
```

**内置别名**：

| 别名 | 类 |
|------|-----|
| `auth` | `Authenticate::class` |
| `guest` | `RedirectIfAuthenticated::class` |
| `throttle` | `ThrottleRequests::class` |
| `csrf` | `VerifyCsrfToken::class` |
| `trim` | `TrimStrings::class` |
| `json` | `ConvertEmptyStringsToNull::class` |

### 6.5 中间件管道

```
Request
  → 全局中间件（按 priority 排序）
  → 分组中间件（按 priority 排序）
  → 路由中间件（按声明顺序）
  → Handler 执行
  → Response（反向经过中间件）
```

### 6.6 中间件接口

```php
interface Middleware
{
    public function handle(Request $request, callable $next, ...$params): Response;
}
```

**示例**：

```php
class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!auth()->check()) {
            return new RedirectResponse('/login');
        }

        return $next($request);  // 继续管道
    }
}

class ThrottleMiddleware
{
    public function handle(Request $request, callable $next, int $max = 60, int $decay = 60): Response
    {
        // 限流逻辑...
        return $next($request);
    }
}
```

---

## 7. 路由匹配与分发

### 7.1 匹配流程

```php
public function dispatch(Request $request): Response
{
    $method = $request->method();
    $path = $request->path();

    // 1. 按 HTTP 方法筛选路由
    foreach ($this->routes->getByMethod($method) as $route) {
        // 2. 正则匹配路径
        $params = $route->match($path);
        if ($params !== false) {
            // 3. 匹配成功 → 执行中间件管道
            return $this->invokeWithMiddleware($route, $request, $params);
        }
    }

    // 4. OPTIONS 请求 → 返回 Allow 头
    // 5. 405 Method Not Allowed
    // 6. 404 Not Found
}
```

### 7.2 路径编译

Route 构造时自动编译路径为正则：

```
/users/{id}           → #^/users/(?P<id>[^/]+)$#
/users/{id}/edit      → #^/users/(?P<id>[^/]+)/edit$#
/files/{path...}      → #^/files/(?P<path>.+)$#
/users/{id:\d+}       → #^/users/(?P<id>\d+)$#  (with where constraint)
```

### 7.3 LiveComponent 特殊处理

当 handler 是 `[LiveComponent子类, '__invoke']` 时：

```php
if ($instance instanceof LiveComponent) {
    $instance->_invoke($params);     // 触发 LiveComponent 生命周期
    if ($result instanceof Response) {
        return $result;
    }
    return $this->normalizeResponse($instance);  // 自动渲染为 HTML Response
}
```

---

## 8. 路由缓存

### 8.1 生成缓存

```bash
php bin/console route:cache
```

将所有路由序列化为 PHP 数组文件，避免每次请求重新扫描。

### 8.2 加载缓存

```php
$router->loadCache($cacheFile);
```

如果缓存文件存在，直接从缓存恢复 `RouteCollection`，跳过 `scan()`。

### 8.3 清除缓存

```bash
php bin/console cache:clear route
```

---

## 9. Handler 类型与响应归一化

### 9.1 Handler 执行结果

| 返回类型 | 处理方式 |
|---------|---------|
| `Response` | 直接返回 |
| `StreamedResponse` | 直接返回 |
| `array` | `Response::json()` |
| `Element` | `Response::html()` |
| `LiveComponent` | `normalizeResponse()` → HTML Response |
| `UXComponent` | `normalizeResponse()` → HTML Response |
| `string` | `Response::html()` |

### 9.2 normalizeResponse 流程

```php
private function normalizeResponse(mixed $result): Response
{
    if ($result instanceof Response) return $result;
    if (is_array($result)) return Response::json($result);
    // ... 其他类型处理
}
```

---

## 10. 完整 API 速查

### Router 方法

| 方法 | 说明 |
|------|------|
| `get($path, $handler, $name)` | 注册 GET 路由 |
| `post($path, $handler, $name)` | 注册 POST 路由 |
| `put($path, $handler, $name)` | 注册 PUT 路由 |
| `patch($path, $handler, $name)` | 注册 PATCH 路由 |
| `delete($path, $handler, $name)` | 注册 DELETE 路由 |
| `options($path, $handler, $name)` | 注册 OPTIONS 路由 |
| `any($path, $handler, $name)` | 注册全方法路由 |
| `match($methods, $path, $handler, $name)` | 注册多方法路由 |
| `addRoute($method, $path, $handler, $name)` | 底层注册方法 |
| `group($attrs, $callback)` | 路由分组 |
| `scan($dirs)` | 扫描目录注册属性路由 |
| `dispatch($request)` | 请求分发 |
| `getRouteByName($name)` | 按名称查找路由 |
| `getRoutes()` | 获取所有路由 |
| `getRouteCollection()` | 获取路由集合 |
| `getMiddlewareManager()` | 获取中间件管理器 |
| `loadCache($file)` | 加载路由缓存 |

### Route 方法

| 方法 | 说明 |
|------|------|
| `where($name, $pattern)` | 参数约束 |
| `defaults($defaults)` | 参数默认值 |
| `middleware($mw)` | 添加中间件 |
| `name($name)` | 设置名称 |
| `match($path)` | 匹配路径，返回参数或 false |
| `generateUrl($params)` | 生成 URL |

### MiddlewareManager 方法

| 方法 | 说明 |
|------|------|
| `use($mw, $priority, $params)` | 注册全局中间件 |
| `group($group, $mw, $priority, $params)` | 注册分组中间件 |
| `alias($alias, $class)` | 注册别名 |
| `resolve($name)` | 解析别名 |
| `pipe($request, $destination, $additional, $group)` | 执行中间件管道 |
