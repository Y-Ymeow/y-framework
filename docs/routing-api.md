# 路由系统 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`CssRoute`](#framework-routing-cssroute)
- [`FileDownloadRoute`](#framework-routing-filedownloadroute)
- [`MediaRoute`](#framework-routing-mediaroute)
- [`Middleware`](#framework-routing-attribute-middleware) — 路由中间件属性
- [`MiddlewareManager`](#framework-routing-middlewaremanager) — 中间件管理器
- [`Route`](#framework-routing-attribute-route)
- [`RouteGroup`](#framework-routing-attribute-routegroup)
- [`Router`](#framework-routing-router)
- [`StaticAssetsRoute`](#framework-routing-staticassetsroute)
- [`SystemRoute`](#framework-routing-systemroute)

---

### 其他

<a name="framework-routing-cssroute"></a>
#### `Framework\Routing\CssRoute`

**文件:** `php/src/Routing/CssRoute.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `handle` |  | `Framework\Http\Request $request` |


<a name="framework-routing-filedownloadroute"></a>
#### `Framework\Routing\FileDownloadRoute`

**文件:** `php/src/Routing/FileDownloadRoute.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `allowExtensions` |  | `array $extensions` |
| `handle` |  | `Framework\Http\Request $request`, `string $path`, `bool $forceDownload` = false |


<a name="framework-routing-mediaroute"></a>
#### `Framework\Routing\MediaRoute`

**文件:** `php/src/Routing/MediaRoute.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `handle` |  | `Framework\Http\Request $request`, `string $path` |


<a name="framework-routing-attribute-middleware"></a>
#### `Framework\Routing\Attribute\Middleware`

路由中间件属性

**文件:** `php/src/Routing/Attribute/Middleware.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$middleware` | `array|string` |  |
| `$priority` | `int` |  |
| `$params` | `array` |  |
| `$only` | `array` |  |
| `$except` | `array` |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `appliesTo` | 检查中间件是否应该应用到当前方法 | `string $methodName` |


<a name="framework-routing-middlewaremanager"></a>
#### `Framework\Routing\MiddlewareManager`

中间件管理器

**文件:** `php/src/Routing/MiddlewareManager.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getInstance` |  | — |
| `reset` |  | — |
| `alias` | 注册中间件别名 | `string $alias`, `string $class` |
| `resolve` | 解析中间件名称（支持别名） | `string $name` |
| `use` | 注册全局中间件 | `array\|string $middleware`, `int $priority` = 0, `array $params` = [] |
| `group` | 注册路由组中间件 | `string $group`, `array\|string $middleware`, `int $priority` = 0, `array $params` = [] |
| `getMiddleware` | 获取按优先级排序的中间件列表 | `?string $group` = null |
| `pipe` | 执行中间件管道 | `Framework\Http\Request $request`, `callable $destination`, `array $additionalMiddleware` = [], `?string $group` = null |


<a name="framework-routing-attribute-route"></a>
#### `Framework\Routing\Attribute\Route`

**文件:** `php/src/Routing/Attribute/Route.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$path` | `string` |  |
| `$methods` | `array|string` |  |
| `$name` | `string` |  |
| `$middleware` | `array` |  |


<a name="framework-routing-attribute-routegroup"></a>
#### `Framework\Routing\Attribute\RouteGroup`

**文件:** `php/src/Routing/Attribute/RouteGroup.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$prefix` | `string` |  |
| `$middleware` | `array` |  |
| `$name` | `string` |  |


<a name="framework-routing-router"></a>
#### `Framework\Routing\Router`

**文件:** `php/src/Routing/Router.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `addRoute` |  | `string $method`, `string $path`, `mixed $handler`, `string $name` = '' |
| `get` |  | `string $path`, `mixed $handler`, `string $name` = '' |
| `post` |  | `string $path`, `mixed $handler`, `string $name` = '' |
| `put` |  | `string $path`, `mixed $handler`, `string $name` = '' |
| `delete` |  | `string $path`, `mixed $handler`, `string $name` = '' |
| `any` |  | `string $path`, `mixed $handler`, `string $name` = '' |
| `loadCache` |  | `string $cacheFile` |
| `scan` |  | `array\|string $directories`, `array $extendFiles` = [] |
| `registerClass` |  | `ReflectionClass $reflection` |
| `dispatch` |  | `Framework\Http\Request $request` |
| `getRoutes` |  | — |


<a name="framework-routing-staticassetsroute"></a>
#### `Framework\Routing\StaticAssetsRoute`

**文件:** `php/src/Routing/StaticAssetsRoute.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `handle` |  | `Framework\Http\Request $request`, `string $path` |


<a name="framework-routing-systemroute"></a>
#### `Framework\Routing\SystemRoute`

**文件:** `php/src/Routing/SystemRoute.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `media` |  | `Framework\Http\Request $request`, `string $path` |
| `assets` |  | `Framework\Http\Request $request`, `string $path` |
| `download` |  | `Framework\Http\Request $request`, `string $path` |
| `stream` |  | `Framework\Http\Request $request`, `string $path` |
| `css` |  | `Framework\Http\Request $request` |
| `js` |  | `Framework\Http\Request $request` |
| `dist` |  | `Framework\Http\Request $request`, `?string $path` = '' |


