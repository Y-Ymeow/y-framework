# HTTP 核心 — API 参考

> 由 DocGen 自动生成于 2026-05-02 05:37:00

## 目录

**其他**
- [`Cookie`](#framework-http-cookie)
- [`HttpClient`](#framework-http-httpclient)
- [`HttpException`](#framework-http-httpexception)
- [`Request`](#framework-http-request)
- [`Response`](#framework-http-response)
- [`Session`](#framework-http-session)
- [`SessionServiceProvider`](#framework-http-sessionserviceprovider)
- [`StaticFile`](#framework-http-staticfile)
- [`StreamedResponse`](#framework-http-streamedresponse)
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

**文件:** `php/src/Http/Response.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `fromSymfony` |  | `Symfony\Component\HttpFoundation\Response $response` |
| `json` |  | `mixed $data`, `int $status` = 200, `array $headers` = [] |
| `html` |  | `mixed $html`, `int $status` = 200, `array $headers` = [] |
| `redirect` |  | `string $url`, `int $status` = 302 |
| `send` |  | — |
| `getSfResponse` |  | — |
| `setHeader` |  | `string $key`, `string $value` |
| `setStatusCode` |  | `int $code` |
| `getStatus` |  | — |
| `getStatusCode` |  | — |
| `getContent` |  | — |


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


<a name="framework-http-sessionserviceprovider"></a>
#### `Framework\Http\SessionServiceProvider`

**继承:** `Framework\Foundation\ServiceProvider`  | **文件:** `php/src/Http/SessionServiceProvider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` |  | — |
| `boot` |  | — |


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


<a name="framework-http-streamedresponse"></a>
#### `Framework\Http\StreamedResponse`

**继承:** `Framework\Http\Response`  | **文件:** `php/src/Http/StreamedResponse.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `send` |  | — |
| `getSfResponse` |  | — |
| `setHeader` |  | `string $key`, `string $value` |
| `setStatusCode` |  | `int $code` |


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


