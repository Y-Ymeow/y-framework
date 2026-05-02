# HTTP 核心 — 开发文档

> 由 DocGen 自动生成于 2026-05-02 05:56:00

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
| `StaticFile` | `Framework\Http` | `php/src/Http/StaticFile.php` | class |
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

**公开方法 (12)：**

- `fromSymfony(Symfony\Component\HttpFoundation\Response $response): Framework\Http\Response`
- `json(mixed $data, int $status = 200, array $headers = []): Framework\Http\Response` — JSON 响应
- `html(mixed $html, int $status = 200, array $headers = []): Framework\Http\Response` — HTML 响应 — 自动适配环境
- `wasm(mixed $html, string $title = '', int $status = 200): Framework\Http\Response` — WASM 专用响应 — 返回结构化数据
- `redirect(string $url, int $status = 302): Framework\Http\Response` — 重定向响应
- `send(): void` — 发送响应
- `getSfResponse(): Symfony\Component\HttpFoundation\Response`
- `setHeader(string $key, string $value): Framework\Http\Response`
- `setStatusCode(int $code): Framework\Http\Response`
- `getStatus(): int`
- `getStatusCode(): int`
- `getContent(): string`

### `Framework\Http\Session`

- **文件:** `php/src/Http/Session.php`

**公开方法 (15)：**

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

### `Framework\Http\SessionServiceProvider`

- **文件:** `php/src/Http/SessionServiceProvider.php`
- **继承:** `Framework\Foundation\ServiceProvider`

**公开方法 (2)：**

- `register(): void`
- `boot(): void`

### `Framework\Http\StaticFile`

- **文件:** `php/src/Http/StaticFile.php`

**公开方法 (4)：**

- `addDir(string $prefix, string $dir): Framework\Http\StaticFile`
- `allowDomains(array $domains): Framework\Http\StaticFile`
- `disableHotlinkProtection(): Framework\Http\StaticFile`
- `serve(string $path, ?string $host = null): void`

### `Framework\Http\StreamedResponse`

- **文件:** `php/src/Http/StreamedResponse.php`
- **继承:** `Framework\Http\Response`

**公开方法 (4)：**

- `send(): void`
- `getSfResponse(): Symfony\Component\HttpFoundation\StreamedResponse`
- `setHeader(string $key, string $value): Framework\Http\StreamedResponse`
- `setStatusCode(int $code): Framework\Http\StreamedResponse`

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

