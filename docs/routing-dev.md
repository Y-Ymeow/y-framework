# 路由系统 — 开发文档

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `CssRoute` | `Framework\Routing` | `php/src/Routing/CssRoute.php` | class |
| `FileDownloadRoute` | `Framework\Routing` | `php/src/Routing/FileDownloadRoute.php` | class |
| `MediaRoute` | `Framework\Routing` | `php/src/Routing/MediaRoute.php` | class |
| `Middleware` | `Framework\Routing\Attribute` | `php/src/Routing/Attribute/Middleware.php` | class |
| `MiddlewareManager` | `Framework\Routing` | `php/src/Routing/MiddlewareManager.php` | class |
| `Route` | `Framework\Routing\Attribute` | `php/src/Routing/Attribute/Route.php` | class |
| `RouteGroup` | `Framework\Routing\Attribute` | `php/src/Routing/Attribute/RouteGroup.php` | class |
| `Router` | `Framework\Routing` | `php/src/Routing/Router.php` | class |
| `StaticAssetsRoute` | `Framework\Routing` | `php/src/Routing/StaticAssetsRoute.php` | class |
| `SystemRoute` | `Framework\Routing` | `php/src/Routing/SystemRoute.php` | class |

---

## 详细实现

### `Framework\Routing\CssRoute`

- **文件:** `php/src/Routing/CssRoute.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request): Framework\Http\Response`

### `Framework\Routing\FileDownloadRoute`

- **文件:** `php/src/Routing/FileDownloadRoute.php`

**公开方法 (2)：**

- `allowExtensions(array $extensions): Framework\Routing\FileDownloadRoute`
- `handle(Framework\Http\Request $request, string $path, bool $forceDownload = false): Framework\Http\Response|Framework\Http\StreamedResponse`

### `Framework\Routing\MediaRoute`

- **文件:** `php/src/Routing/MediaRoute.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, string $path): Framework\Http\Response`

### `Framework\Routing\Attribute\Middleware`

- **文件:** `php/src/Routing/Attribute/Middleware.php`

**公开方法 (1)：**

- `appliesTo(string $methodName): bool` — 检查中间件是否应该应用到当前方法

### `Framework\Routing\MiddlewareManager`

- **文件:** `php/src/Routing/MiddlewareManager.php`

**公开方法 (8)：**

- `getInstance(): Framework\Routing\MiddlewareManager`
- `reset(): void`
- `alias(string $alias, string $class): Framework\Routing\MiddlewareManager` — 注册中间件别名
- `resolve(string $name): string` — 解析中间件名称（支持别名）
- `use(array|string $middleware, int $priority = 0, array $params = []): Framework\Routing\MiddlewareManager` — 注册全局中间件
- `group(string $group, array|string $middleware, int $priority = 0, array $params = []): Framework\Routing\MiddlewareManager` — 注册路由组中间件
- `getMiddleware(?string $group = null): array` — 获取按优先级排序的中间件列表
- `pipe(Framework\Http\Request $request, callable $destination, array $additionalMiddleware = [], ?string $group = null): Framework\Http\Response` — 执行中间件管道

### `Framework\Routing\Attribute\Route`

- **文件:** `php/src/Routing/Attribute/Route.php`

### `Framework\Routing\Attribute\RouteGroup`

- **文件:** `php/src/Routing/Attribute/RouteGroup.php`

### `Framework\Routing\Router`

- **文件:** `php/src/Routing/Router.php`

**公开方法 (11)：**

- `addRoute(string $method, string $path, mixed $handler, string $name = ''): void`
- `get(string $path, mixed $handler, string $name = ''): void`
- `post(string $path, mixed $handler, string $name = ''): void`
- `put(string $path, mixed $handler, string $name = ''): void`
- `delete(string $path, mixed $handler, string $name = ''): void`
- `any(string $path, mixed $handler, string $name = ''): void`
- `loadCache(string $cacheFile): bool`
- `scan(array|string $directories, array $extendFiles = []): void`
- `registerClass(ReflectionClass $reflection): void`
- `dispatch(Framework\Http\Request $request): Framework\Http\Response|Framework\Http\StreamedResponse`
- `getRoutes(): array`

### `Framework\Routing\StaticAssetsRoute`

- **文件:** `php/src/Routing/StaticAssetsRoute.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, string $path): Framework\Http\Response`

### `Framework\Routing\SystemRoute`

- **文件:** `php/src/Routing/SystemRoute.php`

**公开方法 (7)：**

- `media(Framework\Http\Request $request, string $path): Framework\Http\Response`
- `assets(Framework\Http\Request $request, string $path): Framework\Http\Response|Framework\Http\StreamedResponse`
- `download(Framework\Http\Request $request, string $path): Framework\Http\Response|Framework\Http\StreamedResponse`
- `stream(Framework\Http\Request $request, string $path): Framework\Http\Response|Framework\Http\StreamedResponse`
- `css(Framework\Http\Request $request): Framework\Http\Response`
- `js(Framework\Http\Request $request): Framework\Http\Response`
- `dist(Framework\Http\Request $request, ?string $path = ''): Framework\Http\Response|Framework\Http\StreamedResponse`

