# Foundation 核心基础 — 开发文档

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `AppEnvironment` | `Framework\Foundation` | `php/src/Foundation/AppEnvironment.php` | class |
| `Application` | `Framework\Foundation` | `php/src/Foundation/Application.php` | class |
| `Container` | `Framework\Foundation` | `php/src/Foundation/Container.php` | class |
| `Kernel` | `Framework\Foundation` | `php/src/Foundation/Kernel.php` | class |
| `ServiceProvider` | `Framework\Foundation` | `php/src/Foundation/ServiceProvider.php` | abstract |

---

## 详细实现

### `Framework\Foundation\AppEnvironment`

- **文件:** `php/src/Foundation/AppEnvironment.php`

**公开方法 (11)：**

- `detect(): string` — 检测当前运行环境
- `isWasmRuntime(): bool` — 检测是否运行在 WASM 环境
- `isWeb(): bool` — 当前是否为 Web 环境
- `isCli(): bool` — 当前是否为 CLI 环境
- `isWasm(): bool` — 当前是否为 WASM 环境
- `requiresFullDocument(): bool` — 是否需要完整 HTML 文档输出
- `supportsNativeSession(): bool` — 是否支持原生 Session/Cookie
- `supportsHeaders(): bool` — 是否支持 header() 函数
- `setEnvironment(string $env): void` — 强制设置运行环境（用于测试）
- `reset(): void` — 重置检测状态（用于测试）
- `info(): array` — 获取环境信息（用于调试）

### `Framework\Foundation\Application`

- **文件:** `php/src/Foundation/Application.php`

**公开方法 (15)：**

- `getInstance(): ?Framework\Foundation\Application`
- `singleton(string $abstract, mixed $concrete = null): void`
- `bind(string $abstract, mixed $concrete = null): void`
- `alias(string $abstract, string $alias): void`
- `getContainer(): Framework\Foundation\Container`
- `basePath(string $path = ''): string`
- `storagePath(string $path = ''): string`
- `configPath(string $path = ''): string`
- `make(string $class): mixed`
- `makeWith(string $class, array $parameters = []): mixed`
- `instance(string $abstract, mixed $instance): void`
- `register(Framework\Foundation\ServiceProvider $provider): void`
- `boot(): void`
- `bootstrapProviders(): void`
- `isBooted(): bool`

### `Framework\Foundation\Container`

- **文件:** `php/src/Foundation/Container.php`

**公开方法 (8)：**

- `set(string $id, mixed $value): void`
- `get(string $id): mixed`
- `makeWith(string $id, array $extraParams = []): mixed` — 创建实例并传递额外参数
- `has(string $id): bool`
- `singleton(string $id, mixed $concrete = null): void`
- `bind(string $id, mixed $concrete = null): void`
- `alias(string $abstract, string $alias): void`
- `instance(string $id, mixed $instance): void`

### `Framework\Foundation\Kernel`

- **文件:** `php/src/Foundation/Kernel.php`

**公开方法 (4)：**

- `bootstrap(): void`
- `handle(Framework\Http\Request $request): Framework\Http\Response|Framework\Http\StreamedResponse`
- `terminate(Framework\Http\Request $request, Framework\Http\Response|Framework\Http\StreamedResponse $response): void`
- `getRouter(): Framework\Routing\Router`

### `Framework\Foundation\ServiceProvider`

- **文件:** `php/src/Foundation/ServiceProvider.php`

**公开方法 (2)：**

- `register(): void`
- `boot(): void`

