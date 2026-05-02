# 路由系统 — 开发文档

> 由 DocGen 自动生成于 2026-05-02 05:37:00

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `CssRoute` | `Framework\Routing` | `php/src/Routing/CssRoute.php` | class |
| `FileDownloadRoute` | `Framework\Routing` | `php/src/Routing/FileDownloadRoute.php` | class |
| `MediaRoute` | `Framework\Routing` | `php/src/Routing/MediaRoute.php` | class |
| `Middleware` | `Framework\Routing\Attribute` | `php/src/Routing/Attribute/Middleware.php` | class |
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
- `dispatch(Framework\Http\Request $request): Framework\Http\Response`
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

