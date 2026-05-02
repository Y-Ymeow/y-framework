# HTTP 核心 — API 参考

> 由 DocGen 自动生成于 2026-05-02 19:56:28

## 目录

**其他**
- [`Cookie`](#framework-http-cookie)
- [`HttpClient`](#framework-http-httpclient)
- [`HttpException`](#framework-http-httpexception)
- [`Request`](#framework-http-request)
- [`Response`](#framework-http-response) — Response HTTP 响应
- [`Session`](#framework-http-session)
- [`SessionServiceProvider`](#framework-http-sessionserviceprovider)
- [`SseResponse`](#framework-http-sseresponse) — SseResponse 长连接 SSE 响应
- [`StaticFile`](#framework-http-staticfile)
- [`StreamResponse`](#framework-http-streamresponse) — StreamResponse 流式响应
- [`StreamedResponse`](#framework-http-streamedresponse) — StreamedResponse 回调式流式响应
- [`Upload`](#framework-http-upload)

---

### 其他

<a name="framework-http-cookie"></a>
#### `Framework\Http\Cookie`

**文件:** `php/src/Http/Cookie.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `set` |  | `string $name`, `string $value`, `int $expire` = 0, `string $path` = '/', `string $domain` = '', `bool $secure` = false, `bool $httpOnly` = true, `string $sameSite` = 'Lax' |
| `get` |  | `string $name`, `?string $default` = null |
| `has` |  | `string $name` |
| `remove` |  | `string $name`, `string $path` = '/', `string $domain` = '' |
| `forever` |  | `string $name`, `string $value`, `string $path` = '/', `string $domain` = '', `bool $secure` = true, `bool $httpOnly` = true, `string $sameSite` = 'Lax' |
| `forget` |  | `string $name`, `string $path` = '/', `string $domain` = '' |


<a name="framework-http-httpclient"></a>
#### `Framework\Http\HttpClient`

**文件:** `php/src/Http/HttpClient.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` |  | `string $baseUrl` = '' |
| `withHeaders` |  | `array $headers` |
| `withToken` |  | `string $token`, `string $type` = 'Bearer' |
| `withBasicAuth` |  | `string $username`, `string $password` |
| `timeout` |  | `int $seconds` |
| `withoutSslVerification` |  | — |
| `get` |  | `string $url`, `array $query` = [] |
| `post` |  | `string $url`, `mixed $data` = null |
| `put` |  | `string $url`, `mixed $data` = null |
| `patch` |  | `string $url`, `mixed $data` = null |
| `delete` |  | `string $url`, `mixed $data` = null |
| `async` |  | — |


<a name="framework-http-httpexception"></a>
#### `Framework\Http\HttpException`

**继承:** `RuntimeException`  | **实现:** `Stringable, Throwable`  | **文件:** `php/src/Http/HttpException.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getStatusCode` |  | — |


<a name="framework-http-request"></a>
#### `Framework\Http\Request`

**文件:** `php/src/Http/Request.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `method` |  | — |
| `path` |  | — |
| `url` |  | — |
| `get` |  | `string $key`, `mixed $default` = null |
| `input` |  | `string $key`, `mixed $default` = null |
| `query` |  | `string $key`, `mixed $default` = null |
| `post` |  | `string $key`, `mixed $default` = null |
| `json` |  | — |
| `header` |  | `string $key`, `?string $default` = null |
| `all` |  | — |
| `cookie` |  | `string $key`, `?string $default` = null |
| `ip` |  | — |
| `host` |  | — |
| `isMethod` |  | `string $method` |
| `isAjax` |  | — |
| `ajax` |  | — |
| `getRequestUri` |  | — |
| `getUri` |  | — |
| `getMethod` |  | — |
| `isJson` |  | — |
| `expectsJson` |  | — |
| `file` |  | `string $key` |
| `setRoute` |  | `string $name`, `string $handler`, `array $params` = [] |
| `route` |  | — |
| `routeName` |  | — |
| `routeHandler` |  | — |
| `routeParams` |  | — |
| `getSfRequest` |  | — |
| `createFromGlobals` |  | — |
| `create` |  | `string $uri`, `string $method` = 'GET', `array $parameters` = [], `array $cookies` = [], `array $files` = [], `array $server` = [], `mixed $content` = null |


<a name="framework-http-response"></a>
#### `Framework\Http\Response`

Response HTTP 响应

**文件:** `php/src/Http/Response.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `json` | 创建 JSON 响应 | `mixed $data`, `int $status` = 200, `array $headers` = [] |
| `html` | 创建 HTML 响应 | `mixed $html`, `int $status` = 200, `array $headers` = [] |
| `wasm` | 创建 WASM 模式响应 | `mixed $html`, `string $title` = '', `int $status` = 200 |
| `redirect` | 创建重定向响应 | `string $url`, `int $status` = 302 |
| `send` | 发送响应到客户端 | — |
| `setHeader` | 设置响应头 | `string $key`, `string $value` |
| `getHeader` | 获取响应头 | `string $key`, `?string $default` = null |
| `getHeaders` | 获取所有响应头 | — |
| `setStatusCode` | 设置 HTTP 状态码 | `int $code` |
| `getStatus` | 获取 HTTP 状态码 | — |
| `getStatusCode` | 获取 HTTP 状态码（别名） | — |
| `getContent` | 获取响应内容 | — |
| `setContent` | 设置响应内容 | `string $content` |


<a name="framework-http-session"></a>
#### `Framework\Http\Session`

**文件:** `php/src/Http/Session.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `start` |  | — |
| `get` |  | `string $key`, `mixed $default` = null |
| `set` |  | `string $key`, `mixed $value` |
| `has` |  | `string $key` |
| `remove` |  | `string $key` |
| `flash` |  | `string $key`, `mixed $value` |
| `getFlash` |  | `string $key`, `mixed $default` = null |
| `hasFlash` |  | `string $key` |
| `all` |  | — |
| `clear` |  | — |
| `destroy` |  | — |
| `regenerate` |  | — |
| `getId` |  | — |
| `token` |  | — |
| `verifyToken` |  | `string $token` |
| `close` | 关闭 Session（释放锁） | — |
| `isActive` | 检查 Session 是否活跃 | — |


<a name="framework-http-sessionserviceprovider"></a>
#### `Framework\Http\SessionServiceProvider`

**继承:** `Framework\Foundation\ServiceProvider`  | **文件:** `php/src/Http/SessionServiceProvider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` |  | — |
| `boot` |  | — |


<a name="framework-http-sseresponse"></a>
#### `Framework\Http\SseResponse`

SseResponse 长连接 SSE 响应

**继承:** `Framework\Http\Response`  | **文件:** `php/src/Http/SseResponse.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `create` | 创建 SSE 响应实例 | — |
| `event` | 添加初始事件（连接建立时立即发送） | `string $event`, `mixed $data`, `?string $id` = null |
| `keepAlive` | 设置 keep-alive 心跳间隔 | `int $seconds` |
| `onInterval` | 设置定时轮询回调 | `callable $callback`, `int $intervalMs` = 1000 |
| `maxExecTime` | 设置最大执行时间 | `int $seconds` |
| `subscribe` | 订阅 SSE 频道 | `string $channels` |
| `send` | 发送 SSE 响应：发送初始事件 → 进入轮询循环 | — |
| `getContent` | 收集初始事件为字符串 | — |
| `simple` | 快速创建定时推送 SSE | `callable $callback`, `int $intervalMs` = 1000 |


<a name="framework-http-staticfile"></a>
#### `Framework\Http\StaticFile`

**文件:** `php/src/Http/StaticFile.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `addDir` |  | `string $prefix`, `string $dir` |
| `allowDomains` |  | `array $domains` |
| `disableHotlinkProtection` |  | — |
| `serve` |  | `string $path`, `?string $host` = null |


<a name="framework-http-streamresponse"></a>
#### `Framework\Http\StreamResponse`

StreamResponse 流式响应

**继承:** `Framework\Http\Response`  | **文件:** `php/src/Http/StreamResponse.php`

**常量：**

| 常量 | 值 | 说明 |
|---|---|---|
| `FORMAT_NDJSON` | `'ndjson'` | NDJSON 格式：每行一个 JSON 对象 |
| `FORMAT_SSE` | `'sse'` | SSE 格式：Server-Sent Events |
| `FORMAT_TEXT` | `'text'` | 纯文本格式 |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `generator` | 从回调创建流式响应（回调需返回 Generator） | `callable $callback`, `string $format` = 'ndjson' |
| `fromArray` | 从静态数组创建流式响应 | `array $items`, `string $format` = 'ndjson' |
| `textStream` | 创建纯文本流式响应 | `callable $callback`, `float $delay` = 0.01 |
| `getGenerator` | 获取底层 Generator | — |
| `getFormat` | 获取输出格式 | — |
| `send` | 发送流式响应：发送 headers → 清除输出缓冲 → 逐块输出 | — |
| `getContent` | 收集所有 Generator 数据为字符串（会消费整个 Generator） | — |


<a name="framework-http-streamedresponse"></a>
#### `Framework\Http\StreamedResponse`

StreamedResponse 回调式流式响应

**继承:** `Framework\Http\Response`  | **文件:** `php/src/Http/StreamedResponse.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `send` | 发送流式响应（仅执行一次） | — |
| `getContent` | 获取响应内容（会执行回调） | — |
| `setCallback` | 设置输出回调 | `callable $callback` |


<a name="framework-http-upload"></a>
#### `Framework\Http\Upload`

**文件:** `php/src/Http/Upload.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `from` |  | `string $key` |
| `multiple` |  | `string $key` |
| `isValid` |  | — |
| `getName` |  | — |
| `getExtension` |  | — |
| `getMime` |  | — |
| `getSize` |  | — |
| `getTmpName` |  | — |
| `getError` |  | — |
| `getErrorMessage` |  | — |
| `allowedMimes` |  | `array $mimes` |
| `maxSize` |  | `int $bytes` |
| `to` |  | `string $directory` |
| `validate` |  | — |
| `store` |  | `string $directory`, `?string $name` = null |
| `storeAs` |  | `string $directory`, `string $name` |
| `storePublicly` |  | `string $directory`, `?string $name` = null |


