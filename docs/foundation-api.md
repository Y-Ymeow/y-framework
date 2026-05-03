# Foundation 核心基础 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`AppEnvironment`](#framework-foundation-appenvironment) — 应用运行环境检测
- [`Application`](#framework-foundation-application)
- [`Container`](#framework-foundation-container)
- [`Kernel`](#framework-foundation-kernel)
- [`ServiceProvider`](#framework-foundation-serviceprovider)

---

### 其他

<a name="framework-foundation-appenvironment"></a>
#### `Framework\Foundation\AppEnvironment`

应用运行环境检测

**文件:** `php/src/Foundation/AppEnvironment.php`

**常量：**

| 常量 | 值 | 说明 |
|---|---|---|
| `WEB` | `'web'` |  |
| `CLI` | `'cli'` |  |
| `WASM` | `'wasm'` |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `detect` | 检测当前运行环境 | — |
| `isWasmRuntime` | 检测是否运行在 WASM 环境 | — |
| `isWeb` | 当前是否为 Web 环境 | — |
| `isCli` | 当前是否为 CLI 环境 | — |
| `isWasm` | 当前是否为 WASM 环境 | — |
| `requiresFullDocument` | 是否需要完整 HTML 文档输出 | — |
| `supportsNativeSession` | 是否支持原生 Session/Cookie | — |
| `supportsHeaders` | 是否支持 header() 函数 | — |
| `setEnvironment` | 强制设置运行环境（用于测试） | `string $env` |
| `reset` | 重置检测状态（用于测试） | — |
| `info` | 获取环境信息（用于调试） | — |


<a name="framework-foundation-application"></a>
#### `Framework\Foundation\Application`

**文件:** `php/src/Foundation/Application.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getInstance` |  | — |
| `singleton` |  | `string $abstract`, `mixed $concrete` = null |
| `bind` |  | `string $abstract`, `mixed $concrete` = null |
| `alias` |  | `string $abstract`, `string $alias` |
| `getContainer` |  | — |
| `basePath` |  | `string $path` = '' |
| `storagePath` |  | `string $path` = '' |
| `configPath` |  | `string $path` = '' |
| `make` |  | `string $class` |
| `makeWith` |  | `string $class`, `array $parameters` = [] |
| `instance` |  | `string $abstract`, `mixed $instance` |
| `register` |  | `Framework\Foundation\ServiceProvider $provider` |
| `boot` |  | — |
| `bootstrapProviders` |  | — |
| `isBooted` |  | — |


<a name="framework-foundation-container"></a>
#### `Framework\Foundation\Container`

**文件:** `php/src/Foundation/Container.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `set` |  | `string $id`, `mixed $value` |
| `get` |  | `string $id` |
| `makeWith` | 创建实例并传递额外参数 | `string $id`, `array $extraParams` = [] |
| `has` |  | `string $id` |
| `singleton` |  | `string $id`, `mixed $concrete` = null |
| `bind` |  | `string $id`, `mixed $concrete` = null |
| `alias` |  | `string $abstract`, `string $alias` |
| `instance` |  | `string $id`, `mixed $instance` |


<a name="framework-foundation-kernel"></a>
#### `Framework\Foundation\Kernel`

**文件:** `php/src/Foundation/Kernel.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `bootstrap` |  | — |
| `handle` |  | `Framework\Http\Request $request` |
| `terminate` |  | `Framework\Http\Request $request`, `Framework\Http\Response\|Framework\Http\StreamedResponse $response` |
| `getRouter` |  | — |


<a name="framework-foundation-serviceprovider"></a>
#### `Framework\Foundation\ServiceProvider`

**abstract**  | **文件:** `php/src/Foundation/ServiceProvider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` |  | — |
| `boot` |  | — |


