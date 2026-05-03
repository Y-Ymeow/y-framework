# HTTP 核心 — 开发文档

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `Authenticate` | `Framework\Http\Middleware` | `php/src/Http/Middleware/Authenticate.php` | class |
| `ConvertEmptyStringsToNull` | `Framework\Http\Middleware` | `php/src/Http/Middleware/ConvertEmptyStringsToNull.php` | class |
| `Cookie` | `Framework\Http` | `php/src/Http/Cookie.php` | class |
| `HttpClient` | `Framework\Http` | `php/src/Http/HttpClient.php` | class |
| `HttpException` | `Framework\Http` | `php/src/Http/HttpException.php` | extends RuntimeException |
| `RedirectIfAuthenticated` | `Framework\Http\Middleware` | `php/src/Http/Middleware/RedirectIfAuthenticated.php` | class |
| `Request` | `Framework\Http` | `php/src/Http/Request.php` | class |
| `Response` | `Framework\Http` | `php/src/Http/Response.php` | class |
| `Session` | `Framework\Http` | `php/src/Http/Session.php` | class |
| `SessionServiceProvider` | `Framework\Http` | `php/src/Http/SessionServiceProvider.php` | extends Framework\Foundation\ServiceProvider |
| `SseResponse` | `Framework\Http` | `php/src/Http/SseResponse.php` | extends Framework\Http\Response |
| `StaticFile` | `Framework\Http` | `php/src/Http/StaticFile.php` | class |
| `StreamResponse` | `Framework\Http` | `php/src/Http/StreamResponse.php` | extends Framework\Http\Response |
| `StreamedResponse` | `Framework\Http` | `php/src/Http/StreamedResponse.php` | extends Framework\Http\Response |
| `ThrottleRequests` | `Framework\Http\Middleware` | `php/src/Http/Middleware/ThrottleRequests.php` | class |
| `TrimStrings` | `Framework\Http\Middleware` | `php/src/Http/Middleware/TrimStrings.php` | class |
| `Upload` | `Framework\Http` | `php/src/Http/Upload.php` | class |
| `VerifyCsrfToken` | `Framework\Http\Middleware` | `php/src/Http/Middleware/VerifyCsrfToken.php` | class |

---

## 详细实现

### `Framework\Http\Middleware\Authenticate`

- **文件:** `php/src/Http/Middleware/Authenticate.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, callable $next, string $redirectToRoute = 'login'): Framework\Http\Response`

### `Framework\Http\Middleware\ConvertEmptyStringsToNull`

- **文件:** `php/src/Http/Middleware/ConvertEmptyStringsToNull.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, callable $next): Framework\Http\Response`

### `Framework\Http\Cookie`

- **文件:** `php/src/Http/Cookie.php`

**公开方法 (6)：**

- `set(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): void`
- `get(string $name, ?string $default = null): ?string`
- `has(string $name): bool`
- `remove(string $name, string $path = '/', string $domain = ''): void`
- `forever(string $name, string $value, string $path = '/', string $domain = '', bool $secure = true, bool $httpOnly = true, string $sameSite = 'Lax'): void`
- `forget(string $name, string $path = '/', string $domain = ''): void`

### `Framework\Http\HttpClient`

- **文件:** `php/src/Http/HttpClient.php`

**公开方法 (12)：**

- `make(string $baseUrl = ''): Framework\Http\HttpClient`
- `withHeaders(array $headers): Framework\Http\HttpClient`
- `withToken(string $token, string $type = 'Bearer'): Framework\Http\HttpClient`
- `withBasicAuth(string $username, string $password): Framework\Http\HttpClient`
- `timeout(int $seconds): Framework\Http\HttpClient`
- `withoutSslVerification(): Framework\Http\HttpClient`
- `get(string $url, array $query = []): Framework\Http\HttpClientResponse`
- `post(string $url, mixed $data = null): Framework\Http\HttpClientResponse`
- `put(string $url, mixed $data = null): Framework\Http\HttpClientResponse`
- `patch(string $url, mixed $data = null): Framework\Http\HttpClientResponse`
- `delete(string $url, mixed $data = null): Framework\Http\HttpClientResponse`
- `async(): Framework\Http\AsyncHttpClient`

### `Framework\Http\HttpException`

- **文件:** `php/src/Http/HttpException.php`
- **继承:** `RuntimeException`

**公开方法 (1)：**

- `getStatusCode(): int`

### `Framework\Http\Middleware\RedirectIfAuthenticated`

- **文件:** `php/src/Http/Middleware/RedirectIfAuthenticated.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, callable $next, string $redirectToRoute = 'home'): Framework\Http\Response`

### `Framework\Http\Request`

- **文件:** `php/src/Http/Request.php`

**公开方法 (42)：**

- `createFromGlobals(): Framework\Http\Request` — 从 PHP 超全局变量创建请求
- `create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], ?string $content = null): Framework\Http\Request` — 创建模拟请求
- `method(): string` — 获取 HTTP 方法
- `path(): string` — 获取请求路径
- `url(): string` — 获取完整 URL
- `input(string $key, mixed $default = null): mixed` — 获取请求参数（从 query、post、json 中查找）
- `get(string $key, mixed $default = null): mixed` — 获取请求参数（input 的别名）
- `all(): array` — 获取所有请求参数
- `query(string $key, mixed $default = null): mixed` — 获取 query string 参数
- `post(string $key, mixed $default = null): mixed` — 获取 POST 参数
- `json(): ?array` — 解析 JSON 请求体
- `header(string $key, ?string $default = null): ?string` — 获取请求头
- `cookie(string $key, ?string $default = null): ?string` — 获取 Cookie 值
- `file(string $key): ?Framework\Http\Upload` — 获取上传文件
- `ip(): string` — 获取客户端 IP
- `host(): string` — 获取主机名
- `isMethod(string $method): bool` — 判断是否为指定 HTTP 方法
- `isAjax(): bool` — 判断是否为 AJAX 请求
- `ajax(): bool` — 判断是否为 AJAX 请求（别名）
- `getRequestUri(): string` — 获取请求 URI
- `getUri(): string` — 获取完整 URI
- `getMethod(): string` — 获取 HTTP 方法
- `isJson(): bool` — 判断是否为 JSON 请求
- `expectsJson(): bool` — 判断客户端是否期望 JSON 响应
- `getContent(): string` — 获取请求体原始内容
- `setRoute(string $name, string $handler, array $params = []): Framework\Http\Request` — 设置路由信息
- `route(): ?object` — 获取路由信息对象
- `routeName(): ?string` — 获取路由名称
- `routeHandler(): ?string` — 获取路由处理器
- `routeParams(): array` — 获取路由参数
- `has(string $key): bool` — 检查参数是否存在
- `bearerToken(): ?string` — 获取 Bearer Token
- `userAgent(): string` — 获取 User-Agent
- `isSafe(): bool` — 判断请求是否安全（GET/HEAD/OPTIONS）
- `setInput(string $key, mixed $value): Framework\Http\Request` — 设置请求参数
- `merge(array $params): Framework\Http\Request` — 合并请求参数
- `setHeader(string $key, string $value): Framework\Http\Request` — 设置请求头
- `setMethod(string $method): Framework\Http\Request` — 设置 HTTP 方法
- `setRequestUri(string $uri): Framework\Http\Request` — 设置请求 URI
- `setContent(string $content): Framework\Http\Request` — 设置请求体内容
- `setCookie(string $key, string $value): Framework\Http\Request` — 设置 Cookie
- `removeInput(string $key): Framework\Http\Request` — 移除请求参数

### `Framework\Http\Response`

- **文件:** `php/src/Http/Response.php`

**公开方法 (13)：**

- `json(mixed $data, int $status = 200, array $headers = []): Framework\Http\Response` — 创建 JSON 响应
- `html(mixed $html, int $status = 200, array $headers = []): Framework\Http\Response` — 创建 HTML 响应
- `wasm(mixed $html, string $title = '', int $status = 200): Framework\Http\Response` — 创建 WASM 模式响应
- `redirect(string $url, int $status = 302): Framework\Http\Response` — 创建重定向响应
- `send(): void` — 发送响应到客户端
- `setHeader(string $key, string $value): Framework\Http\Response` — 设置响应头
- `getHeader(string $key, ?string $default = null): ?string` — 获取响应头
- `getHeaders(): array` — 获取所有响应头
- `setStatusCode(int $code): Framework\Http\Response` — 设置 HTTP 状态码
- `getStatus(): int` — 获取 HTTP 状态码
- `getStatusCode(): int` — 获取 HTTP 状态码（别名）
- `getContent(): string` — 获取响应内容
- `setContent(string $content): Framework\Http\Response` — 设置响应内容

### `Framework\Http\Session`

- **文件:** `php/src/Http/Session.php`

**公开方法 (17)：**

- `start(): void`
- `get(string $key, mixed $default = null): mixed`
- `set(string $key, mixed $value): void`
- `has(string $key): bool`
- `remove(string $key): void`
- `flash(string $key, mixed $value): void`
- `getFlash(string $key, mixed $default = null): mixed`
- `hasFlash(string $key): bool`
- `all(): array`
- `clear(): void`
- `destroy(): void`
- `regenerate(): void`
- `getId(): string`
- `token(): string`
- `verifyToken(string $token): bool`
- `close(): void` — 关闭 Session（释放锁）
- `isActive(): bool` — 检查 Session 是否活跃

### `Framework\Http\SessionServiceProvider`

- **文件:** `php/src/Http/SessionServiceProvider.php`
- **继承:** `Framework\Foundation\ServiceProvider`

**公开方法 (2)：**

- `register(): void`
- `boot(): void`

### `Framework\Http\SseResponse`

- **文件:** `php/src/Http/SseResponse.php`
- **继承:** `Framework\Http\Response`

**公开方法 (9)：**

- `create(): Framework\Http\SseResponse` — 创建 SSE 响应实例
- `event(string $event, mixed $data, ?string $id = null): Framework\Http\SseResponse` — 添加初始事件（连接建立时立即发送）
- `keepAlive(int $seconds): Framework\Http\SseResponse` — 设置 keep-alive 心跳间隔
- `onInterval(callable $callback, int $intervalMs = 1000): Framework\Http\SseResponse` — 设置定时轮询回调
- `maxExecTime(int $seconds): Framework\Http\SseResponse` — 设置最大执行时间
- `subscribe(string $channels): Framework\Http\SseResponse` — 订阅 SSE 频道
- `send(): void` — 发送 SSE 响应：发送初始事件 → 进入轮询循环
- `getContent(): string` — 收集初始事件为字符串
- `simple(callable $callback, int $intervalMs = 1000): Framework\Http\SseResponse` — 快速创建定时推送 SSE

### `Framework\Http\StaticFile`

- **文件:** `php/src/Http/StaticFile.php`

**公开方法 (4)：**

- `addDir(string $prefix, string $dir): Framework\Http\StaticFile`
- `allowDomains(array $domains): Framework\Http\StaticFile`
- `disableHotlinkProtection(): Framework\Http\StaticFile`
- `serve(string $path, ?string $host = null): void`

### `Framework\Http\StreamResponse`

- **文件:** `php/src/Http/StreamResponse.php`
- **继承:** `Framework\Http\Response`

**公开方法 (7)：**

- `generator(callable $callback, string $format = 'ndjson'): Framework\Http\StreamResponse` — 从回调创建流式响应（回调需返回 Generator）
- `fromArray(array $items, string $format = 'ndjson'): Framework\Http\StreamResponse` — 从静态数组创建流式响应
- `textStream(callable $callback, float $delay = 0.01): Framework\Http\StreamResponse` — 创建纯文本流式响应
- `getGenerator(): Generator` — 获取底层 Generator
- `getFormat(): string` — 获取输出格式
- `send(): void` — 发送流式响应：发送 headers → 清除输出缓冲 → 逐块输出
- `getContent(): string` — 收集所有 Generator 数据为字符串（会消费整个 Generator）

### `Framework\Http\StreamedResponse`

- **文件:** `php/src/Http/StreamedResponse.php`
- **继承:** `Framework\Http\Response`

**公开方法 (3)：**

- `send(): void` — 发送流式响应（仅执行一次）
- `getContent(): string` — 获取响应内容（会执行回调）
- `setCallback(callable $callback): Framework\Http\StreamedResponse` — 设置输出回调

### `Framework\Http\Middleware\ThrottleRequests`

- **文件:** `php/src/Http/Middleware/ThrottleRequests.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, callable $next, int $maxAttempts = 60, int $decayMinutes = 1): Framework\Http\Response`

### `Framework\Http\Middleware\TrimStrings`

- **文件:** `php/src/Http/Middleware/TrimStrings.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, callable $next): Framework\Http\Response`

### `Framework\Http\Upload`

- **文件:** `php/src/Http/Upload.php`

**公开方法 (17)：**

- `from(string $key): ?Framework\Http\Upload`
- `multiple(string $key): array`
- `isValid(): bool`
- `getName(): string`
- `getExtension(): string`
- `getMime(): string`
- `getSize(): int`
- `getTmpName(): string`
- `getError(): int`
- `getErrorMessage(): string`
- `allowedMimes(array $mimes): Framework\Http\Upload`
- `maxSize(int $bytes): Framework\Http\Upload`
- `to(string $directory): Framework\Http\Upload`
- `validate(): array`
- `store(string $directory, ?string $name = null): string`
- `storeAs(string $directory, string $name): string`
- `storePublicly(string $directory, ?string $name = null): string`

### `Framework\Http\Middleware\VerifyCsrfToken`

- **文件:** `php/src/Http/Middleware/VerifyCsrfToken.php`

**公开方法 (1)：**

- `handle(Framework\Http\Request $request, callable $next): Framework\Http\Response`

