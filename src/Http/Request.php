<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * Request HTTP 请求
 *
 * 框架核心 HTTP 请求类，纯 PHP 实现，无第三方依赖。
 * 自动适配 Web/WASM 双模式运行环境。
 *
 * @http-category Request
 * @http-since 2.0
 *
 * @http-example
 * $request = Request::createFromGlobals();
 * $name = $request->input('name');
 * $method = $request->method();
 * $uri = $request->getRequestUri();
 * @http-example-end
 */
class Request
{
    private array $query = [];
    private array $post = [];
    private array $cookies = [];
    private array $files = [];
    private array $server = [];
    private array $headers = [];
    private ?string $content = null;
    private ?array $jsonCache = null;

    private ?\Closure $routeCallback = null;
    private array $routeParams = [];
    private ?string $routeName = null;
    private ?string $routeHandler = null;

    public function __construct(
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->content = $content;

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = str_replace('_', '-', substr($key, 5));
                $this->headers[$headerKey] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $this->headers['CONTENT-TYPE'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $this->headers['CONTENT-LENGTH'] = $server['CONTENT_LENGTH'];
        }
    }

    /**
     * 从 PHP 超全局变量创建请求
     * @return static
     * @http-example Request::createFromGlobals()
     */
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, null);
    }

    /**
     * 创建模拟请求
     * @param string $uri 请求 URI
     * @param string $method HTTP 方法
     * @param array $parameters 请求参数
     * @param array $cookies Cookie
     * @param array $files 上传文件
     * @param array $server 服务器变量
     * @param string|null $content 请求体
     * @return static
     * @http-example Request::create('/api/users', 'POST', ['name' => 'John'])
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): self {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Framework/2.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $server);

        $server['REQUEST_URI'] = $uri;
        $server['REQUEST_METHOD'] = strtoupper($method);

        $qs = parse_url($uri, PHP_URL_QUERY);
        if ($qs) {
            $server['QUERY_STRING'] = $qs;
        }

        $query = [];
        $post = [];

        if ($method === 'GET') {
            $query = $parameters;
        } else {
            $post = $parameters;
            if (!isset($server['CONTENT_TYPE'])) {
                $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
            }
        }

        if ($content !== null) {
            $server['CONTENT_TYPE'] = $server['CONTENT_TYPE'] ?? 'application/json';
        }

        return new self($query, $post, $cookies, $files, $server, $content);
    }

    /**
     * 获取 HTTP 方法
     * @return string
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * 获取请求路径
     * @return string
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }

    /**
     * 获取完整 URL
     * @return string
     */
    public function url(): string
    {
        return $this->getUri();
    }

    /**
     * 获取请求参数（从 query、post、json 中查找）
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     * @http-example $request->input('name', 'default')
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($this->post[$key])) {
            return $this->post[$key];
        }

        if (isset($this->query[$key])) {
            return $this->query[$key];
        }

        if ($this->isJson()) {
            $json = $this->json();
            if ($json !== null && array_key_exists($key, $json)) {
                return $json[$key];
            }
        }

        return $default;
    }

    /**
     * 获取请求参数（input 的别名）
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * 获取所有请求参数
     * @return array
     * @http-example $request->all()
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json() ?? []);
    }

    /**
     * 获取 query string 参数
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * 获取 POST 参数
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * 解析 JSON 请求体
     * @return array|null
     */
    public function json(): ?array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        $content = $this->getContent();
        if (empty($content)) {
            return $this->jsonCache = [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return $this->jsonCache = [];
        }

        return $this->jsonCache = $decoded;
    }

    /**
     * 获取请求头
     * @param string $key 头名称
     * @param string|null $default 默认值
     * @return string|null
     * @http-example $request->header('x-csrf-token')
     */
    public function header(string $key, ?string $default = null): ?string
    {
        $normalizedKey = strtoupper(str_replace('-', '_', $key));

        if (isset($this->headers[$normalizedKey])) {
            return $this->headers[$normalizedKey];
        }

        foreach ($this->headers as $headerKey => $value) {
            if (strtoupper(str_replace('-', '_', $headerKey)) === $normalizedKey) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * 获取 Cookie 值
     * @param string $key Cookie 名
     * @param string|null $default 默认值
     * @return string|null
     */
    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * 获取上传文件
     * @param string $key 文件字段名
     * @return Upload|null
     */
    public function file(string $key): ?Upload
    {
        return Upload::from($key);
    }

    /**
     * 获取客户端 IP
     * @return string
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['HTTP_X_REAL_IP']
            ?? $this->server['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    /**
     * 获取主机名
     * @return string
     */
    public function host(): string
    {
        return $this->server['HTTP_HOST']
            ?? $this->server['SERVER_NAME']
            ?? 'localhost';
    }

    /**
     * 判断是否为指定 HTTP 方法
     * @param string $method HTTP 方法
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    /**
     * 判断是否为 AJAX 请求
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * 判断是否为 AJAX 请求（别名）
     * @return bool
     */
    public function ajax(): bool
    {
        return $this->isAjax();
    }

    /**
     * 获取请求 URI
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * 获取完整 URI
     * @return string
     */
    public function getUri(): string
    {
        $host = $this->host();
        $scheme = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        $uri = $this->getRequestUri();

        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * 获取 HTTP 方法
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method();
    }

    /**
     * 判断是否为 JSON 请求
     * @return bool
     */
    public function isJson(): bool
    {
        $ct = $this->header('Content-Type', '');
        return str_contains($ct, 'application/json');
    }

    /**
     * 判断客户端是否期望 JSON 响应
     * @return bool
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    /**
     * 获取请求体原始内容
     * @return string
     */
    public function getContent(): string
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $input = file_get_contents('php://input');
        if ($input !== false && $input !== '') {
            return $this->content = $input;
        }

        if (!empty($this->post)) {
            return $this->content = http_build_query($this->post);
        }

        return $this->content = '';
    }

    /**
     * 设置路由信息
     * @param string $name 路由名称
     * @param string $handler 处理器
     * @param array $params 路由参数
     * @return static
     */
    public function setRoute(string $name, string $handler, array $params = []): self
    {
        $this->routeName = $name;
        $this->routeHandler = $handler;
        $this->routeParams = $params;
        return $this;
    }

    /**
     * 获取路由信息对象
     * @return object|null
     */
    public function route(): ?object
    {
        if ($this->routeName === null) {
            return null;
        }
        return new class($this->routeName, $this->routeHandler, $this->routeParams) {
            public function __construct(
                private string $name,
                private string $handler,
                private array $params
            ) {}
            public function getName(): string { return $this->name; }
            public function getActionName(): string { return $this->handler; }
            public function getHandler(): string { return $this->handler; }
            public function parameters(): array { return $this->params; }
        };
    }

    /** 获取路由名称 */
    public function routeName(): ?string { return $this->routeName; }

    /** 获取路由处理器 */
    public function routeHandler(): ?string { return $this->routeHandler; }

    /** 获取路由参数 */
    public function routeParams(): array { return $this->routeParams; }

    /**
     * 检查参数是否存在
     * @param string $key 参数名
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post)
            || array_key_exists($key, $this->query)
            || array_key_exists($key, $this->json() ?? []);
    }

    /**
     * 获取 Bearer Token
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    /**
     * 获取 User-Agent
     * @return string
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * 判断请求是否安全（GET/HEAD/OPTIONS）
     * @return bool
     */
    public function isSafe(): bool
    {
        return in_array($this->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * 设置请求参数
     * @param string $key 参数名
     * @param mixed $value 参数值
     * @return static
     * @http-example $request->setInput('name', 'John')
     */
    public function setInput(string $key, mixed $value): self
    {
        $this->post[$key] = $value;
        $this->jsonCache = null;
        return $this;
    }

    /**
     * 合并请求参数
     * @param array $params 参数数组
     * @return static
     * @http-example $request->merge(['name' => 'John', 'age' => 30])
     */
    public function merge(array $params): self
    {
        $this->post = array_merge($this->post, $params);
        $this->jsonCache = null;
        return $this;
    }

    /**
     * 设置请求头
     * @param string $key 头名称
     * @param string $value 头值
     * @return static
     */
    public function setHeader(string $key, string $value): self
    {
        $normalizedKey = strtoupper(str_replace('-', '_', $key));
        $this->headers[$normalizedKey] = $value;
        $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($key));
        $this->server[$serverKey] = $value;
        return $this;
    }

    /**
     * 设置 HTTP 方法
     * @param string $method HTTP 方法
     * @return static
     */
    public function setMethod(string $method): self
    {
        $this->server['REQUEST_METHOD'] = strtoupper($method);
        return $this;
    }

    /**
     * 设置请求 URI
     * @param string $uri 请求 URI
     * @return static
     */
    public function setRequestUri(string $uri): self
    {
        $this->server['REQUEST_URI'] = $uri;
        return $this;
    }

    /**
     * 设置请求体内容
     * @param string $content 请求体
     * @return static
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        $this->jsonCache = null;
        return $this;
    }

    /**
     * 设置 Cookie
     * @param string $key Cookie 名
     * @param string $value Cookie 值
     * @return static
     */
    public function setCookie(string $key, string $value): self
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * 移除请求参数
     * @param string $key 参数名
     * @return static
     */
    public function removeInput(string $key): self
    {
        unset($this->post[$key], $this->query[$key]);
        if ($this->jsonCache !== null) {
            unset($this->jsonCache[$key]);
        }
        return $this;
    }
}
