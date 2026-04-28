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

Admin 后台使用通用的 Resource 路由模式，通过 `AdminResourceController` 统一处理所有 Resource 的 CRUD 路由：

### 路由结构

| 路由 | HTTP 方法 | 用途 |
|------|-----------|------|
| `/admin/{resource}` | GET | 资源列表页（index） |
| `/admin/{resource}/create` | GET | 创建资源表单（create） |
| `/admin/{resource}/{id}/edit` | GET | 编辑资源表单（edit） |

### Resource 定义

Resource 类定义资源配置（表单、表格），不需要手动注册路由：

```php
use Framework\Admin\Attribute\AdminResource;
use Framework\Admin\ResourceInterface;

#[AdminResource(
    name: 'products',
    model: Product::class,
    title: '商品管理',
    icon: 'heroicon.shopping-bag',
)]
class ProductResource implements ResourceInterface
{
    public static function getName(): string { return 'products'; }
    public static function getModel(): string { return Product::class; }
    public static function getTitle(): string { return '商品管理'; }
    
    public function configureForm(FormBuilder $form): void { ... }
    public function configureTable(DataTable $table): void { ... }
}
```

### 工作原理

1. `AttributeScanner` 扫描 `admin/Resources` 目录，读取 `#[AdminResource]` 属性
2. 注册 Resource 到 `AdminManager`（不注册路由）
3. `AdminResourceController` 的通配符路由 `/admin/{resource}` 匹配所有 Resource 路径
4. 控制器根据 `{resource}` 参数从 `AdminManager` 获取对应的 Resource 类
5. 使用 `AdminLayout` 包裹页面，提供统一的侧边栏、header、footer

### 自定义路由

如果 Resource 需要自定义路由（如特殊权限、自定义页面），可以在 Resource 类中直接定义路由方法：

```php
#[AdminResource(name: 'products')]
class ProductResource
{
    #[Get('/export')]
    public function export(): Response { ... }
    
    #[Post('/bulk-import')]
    public function bulkImport(Request $request): Response { ... }
}
```

自定义路由会优先于通配符路由被匹配（如果扫描顺序配置正确）。

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
