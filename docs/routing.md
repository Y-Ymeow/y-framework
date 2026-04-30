# 路由系统

## 概述

基于 PHP 8 Attribute 的路由注册，支持类级别和方法级别。同时支持手动注册路由和通配符路径匹配。

## Route 属性

类级别路由，标记类为路由类：

```php
use Framework\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController
{
    // 所有方法路由自动添加 /admin 前缀
}
```

## HTTP 方法属性

```php
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Post;
use Framework\Routing\Attribute\Put;
use Framework\Routing\Attribute\Delete;

#[Route('/users')]
class UserController
{
    #[Get('/')]
    public function index(): Response { ... }

    #[Get('/{id}')]
    public function show(int $id): Response { ... }

    #[Post('/')]
    public function store(): Response { ... }

    #[Put('/{id}')]
    public function update(int $id): Response { ... }

    #[Delete('/{id}')]
    public function destroy(int $id): Response { ... }
}
```

## Middleware 属性

```php
use Framework\Routing\Attribute\Middleware;

#[Route('/admin')]
#[Middleware(['auth', 'admin'])]
class AdminController
{
    #[Get('/')]
    public function index(): Response { ... }
}
```

## 手动注册路由

支持手动注册路由，可使用闭包作为处理器：

```php
$router = $app->make(Router::class);

// 基本路由
$router->get('/hello', fn() => 'Hello World');

// 带参数的路由
$router->get('/user/{id}', fn(int $id) => "User: {$id}");

// 多种 HTTP 方法
$router->post('/api/data', fn(Request $request) => ['status' => 'ok']);
$router->put('/api/data/{id}', fn(int $id, Request $request) => ['updated' => $id]);
$router->delete('/api/data/{id}', fn(int $id) => ['deleted' => $id]);

// 匹配所有 HTTP 方法
$router->any('/api/{path...}', fn(string $path) => "API: {$path}");
```

## 通配符路由

使用 `{param...}` 语法匹配任意层级的路径：

```php
// 匹配 /files/a/b/c/d.txt → $path = 'a/b/c/d.txt'
$router->get('/files/{path...}', fn(string $path) => $this->serveFile($path));

// 匹配 /api/v1/users/123/posts → $endpoint = 'v1/users/123/posts'
$router->any('/api/{endpoint...}', fn(string $endpoint) => $this->handleApi($endpoint));
```

## 系统路由

框架内置以下系统路由：

| 路由 | 用途 | 处理类 |
|------|------|--------|
| `/media/{path...}` | 图片媒体服务 | `MediaRoute` |
| `/assets/{path...}` | CSS/JS/项目资源 | `StaticAssetsRoute` |
| `/download/{path...}` | 文件下载（支持断点续传） | `FileDownloadRoute` |
| `/stream/{path...}` | 流式传输（视频/音频） | `FileDownloadRoute` |

## 路由扫描

Router 自动扫描指定目录下的 PHP 文件，注册带 Attribute 的路由：

```php
$router->scan(['admin/Pages', 'app/Controllers']);
```

## 路由参数

路由参数通过方法参数自动注入：

```php
#[Get('/users/{id}')]
public function show(int $id): Response
{
    // $id 自动从 URL 提取
}

#[Get('/posts/{slug}')]
public function post(string $slug): Response
{
    // $slug 自动从 URL 提取
}
```

## 请求注入

`Request` 对象也可以自动注入：

```php
#[Post('/users')]
public function store(Request $request): Response
{
    $name = $request->input('name');
    $email = $request->input('email');
}
```

## 查看路由列表

```bash
php bin/console route:list
```

## Admin 路由系统

Admin 后台使用 Resource 路由模式。每个 Resource 类定义其资源配置（表单、表格）并自主注册其所需的路由。

### 路由结构

默认情况下，Resource 继承自 `BaseResource` 后会自动注册以下路由：

| 路由名称 | 默认路径 | 处理器方法 | 用途 |
|----------|----------|------------|------|
| `admin.resource.{name}` | `/admin/{resource}` | `renderList` | 资源列表页 |
| `admin.resource.{name}.create` | `/admin/{resource}/create` | `renderCreate` | 创建资源表单 |
| `admin.resource.{name}.edit` | `/admin/{resource}/{id}/edit` | `renderEdit` | 编辑资源表单 |

### Resource 定义

Resource 类通过继承 `BaseResource` 获取默认的路由注册能力：

```php
use Framework\Admin\Attribute\AdminResource;
use Framework\Admin\Resource\BaseResource;

#[AdminResource(
    name: 'products',
    model: Product::class,
    title: '商品管理',
    icon: 'heroicon.shopping-bag',
)]
class ProductResource extends BaseResource
{
    public static function getName(): string { return 'products'; }
    public static function getModel(): string { return Product::class; }
    public static function getTitle(): string { return '商品管理'; }
    
    public function configureForm(FormBuilder $form): void { ... }
    public function configureTable(DataTable $table): void { ... }
}
```

### 工作原理

1. `AttributeScanner` 扫描 `admin/Resources` 目录，通过 `#[AdminResource]` 属性将 Resource 注册到 `AdminManager`。
2. 在 `AdminServiceProvider` 引导过程中，调用 `AdminManager::registerRoutes()`。
3. `AdminManager` 遍历所有已注册的 Resource，调用它们的 `getRoutes()` 静态方法。
4. 路由被逐一注册到系统的 `Router` 中。

### 自定义路由

Resource 可以通过重写 `getRoutes()` 方法来增加、修改或移除路由：

```php
class ProductResource extends BaseResource
{
    public static function getRoutes(): array
    {
        $routes = parent::getRoutes();
        $name = static::getName();
        
        // 添加自定义页面路由
        $routes["admin.resource.{$name}.stats"] = [
            'method' => 'GET',
            'path' => "/{$name}/stats",
            'handler' => [static::class, 'renderStats'],
        ];
        
        return $routes;
    }

    public static function renderStats(): Response
    {
        // 渲染自定义统计页面的逻辑
    }
}
```

这种机制比之前的通配符路由更灵活，因为它允许为每个资源精确定义路由和处理器，同时也方便了 URL 的生成和命名路由的使用。

输出示例：
```
+---------+---------------------+---------------------------------------------------+
| Method  | Path                | Handler                                           |
+---------+---------------------+---------------------------------------------------+
| GET     | /admin/demo         | Admin\Pages\DemoLivePage@index                    |
| GET     | /media/{path...}    | Closure                                           |
| GET     | /assets/{path...}   | Closure                                           |
| GET     | /download/{path...} | Closure                                           |
| GET     | /stream/{path...}   | Closure                                           |
+---------+---------------------+---------------------------------------------------+
```
