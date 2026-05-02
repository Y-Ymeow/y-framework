# HTTP 核心 — 开发文档

> 由 DocGen 自动生成于 2026-05-02 19:56:28

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `Cookie` | `Framework\Http` | `php/src/Http/Cookie.php` | class |
| `HttpClient` | `Framework\Http` | `php/src/Http/HttpClient.php` | class |
| `HttpException` | `Framework\Http` | `php/src/Http/HttpException.php` | extends RuntimeException |
| `Request` | `Framework\Http` | `php/src/Http/Request.php` | class |
| `Response` | `Framework\Http` | `php/src/Http/Response.php` | class |
| `Session` | `Framework\Http` | `php/src/Http/Session.php` | class |
| `SessionServiceProvider` | `Framework\Http` | `php/src/Http/SessionServiceProvider.php` | extends Framework\Foundation\ServiceProvider |
| `SseResponse` | `Framework\Http` | `php/src/Http/SseResponse.php` | extends Framework\Http\Response |
| `StaticFile` | `Framework\Http` | `php/src/Http/StaticFile.php` | class |
| `StreamResponse` | `Framework\Http` | `php/src/Http/StreamResponse.php` | extends Framework\Http\Response |
| `StreamedResponse` | `Framework\Http` | `php/src/Http/StreamedResponse.php` | extends Framework\Http\Response |
| `Upload` | `Framework\Http` | `php/src/Http/Upload.php` | class |

---

## 详细实现

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

### `Framework\Http\Request`

- **文件:** `php/src/Http/Request.php`

**公开方法 (30)：**

- `method(): string`
- `path(): string`
- `url(): string`
- `get(string $key, mixed $default = null): mixed`
- `input(string $key, mixed $default = null): mixed`
- `query(string $key, mixed $default = null): mixed`
- `post(string $key, mixed $default = null): mixed`
- `json(): ?array`
- `header(string $key, ?string $default = null): ?string`
- `all(): array`
- `cookie(string $key, ?string $default = null): ?string`
- `ip(): string`
- `host(): string`
- `isMethod(string $method): bool`
- `isAjax(): bool`
- `ajax(): bool`
- `getRequestUri(): string`
- `getUri(): string`
- `getMethod(): string`
- `isJson(): bool`
- `expectsJson(): bool`
- `file(string $key): ?Symfony\Component\HttpFoundation\File\UploadedFile`
- `setRoute(string $name, string $handler, array $params = []): Framework\Http\Request`
- `route(): ?object`
- `routeName(): ?string`
- `routeHandler(): ?string`
- `routeParams(): array`
- `getSfRequest(): Symfony\Component\HttpFoundation\Request`
- `createFromGlobals(): Framework\Http\Request`
- `create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], mixed $content = null): Framework\Http\Request`

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

