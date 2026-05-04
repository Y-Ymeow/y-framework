<?php

declare(strict_types=1);

namespace Framework\Http\Request;

use Framework\Http\Upload;

/**
 * Request 核心类
 *
 * 组合 InputBag + HeaderBag + ServerBag + RouteInfo，通过委托组合。
 * 不再是一个 600+ 行的上帝类。
 */
class Request
{
    private ?string $rawContent = null;
    private ?array $files = null;

    public readonly InputBag $input;
    public readonly HeaderBag $headers;
    public readonly ServerBag $server;
    public readonly RouteInfo $route;

    public function __construct(
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ) {
        $this->input = new InputBag($query, $post);
        $this->headers = HeaderBag::fromServer($server);
        $this->server = new ServerBag($server);
        $this->route = new RouteInfo();
        $this->cookies = $cookies;
        $this->files = $files;
        $this->rawContent = $content;

        if ($this->isJson() && $content !== null) {
            $this->input->parseJson($content);
        }
    }

    private array $cookies = [];

    // ── 工厂方法 ──

    public static function createFromGlobals(): self
    {
        $post = $_POST;
        $content = null;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $post = array_merge($post, $decoded);
                }
                $content = $raw;
            }
        }

        return new self($_GET, $post, $_COOKIE, $_FILES, $_SERVER, $content);
    }

    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
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
            $server['CONTENT_TYPE'] ??= 'application/x-www-form-urlencoded';
        }

        if ($content !== null) {
            $server['CONTENT_TYPE'] ??= 'application/json';
        }

        return new self($query, $post, $cookies, $files, $server, $content);
    }

    // ── 请求基本信息 ──

    public function method(): string
    {
        return $this->server->getMethod();
    }

    public function path(): string
    {
        return $this->server->getPath();
    }

    public function url(): string
    {
        return $this->getUri();
    }

    public function getRequestUri(): string
    {
        return $this->server->getRequestUri();
    }

    public function getUri(): string
    {
        return $this->server->getScheme() . '://' . $this->server->getHost() . $this->server->getRequestUri();
    }

    public function getMethod(): string
    {
        return $this->method();
    }

    // ── 参数 ──

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->json() !== null && array_key_exists($key, $this->json())) {
            return $this->json()[$key];
        }
        return $this->input->get($key, $default);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    public function all(): array
    {
        return $this->input->all();
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->input->query($key, $default);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->input->post($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->input->has($key);
    }

    public function setInput(string $key, mixed $value): self
    {
        $this->input->set($key, $value);
        return $this;
    }

    public function merge(array $params): self
    {
        $this->input->merge($params);
        return $this;
    }

    public function removeInput(string $key): self
    {
        $this->input->remove($key);
        return $this;
    }

    // ── JSON ──

    public function json(): ?array
    {
        return $this->input->json();
    }

    public function isJson(): bool
    {
        return $this->headers->isJson();
    }

    // ── 请求头 ──

    public function header(string $key, ?string $default = null): ?string
    {
        return $this->headers->get($key, $default);
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers->set($key, $value);
        $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($key));
        $this->server->set($serverKey, $value);
        return $this;
    }

    // ── Cookie ──

    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    public function setCookie(string $key, string $value): self
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    // ── 文件 ──

    public function file(string $key): ?Upload
    {
        return Upload::from($key);
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    // ── 请求体 ──

    public function getContent(): string
    {
        if ($this->rawContent !== null) {
            return $this->rawContent;
        }

        $input = file_get_contents('php://input');
        if ($input !== false && $input !== '') {
            return $this->rawContent = $input;
        }

        $post = $this->input->getPostParams();
        if ($post !== []) {
            return $this->rawContent = http_build_query($post);
        }

        return $this->rawContent = '';
    }

    public function setContent(string $content): self
    {
        $this->rawContent = $content;
        if ($this->isJson()) {
            $this->input->parseJson($content);
        }
        return $this;
    }

    // ── 路由 ──

    public function setRoute(string $name, string $handler, array $params = []): self
    {
        $this->route->set($name, $handler, $params);
        return $this;
    }

    public function route(): ?object
    {
        if (!$this->route->isResolved()) {
            return null;
        }
        return $this->route->toObject();
    }

    public function routeName(): ?string
    {
        return $this->route->getName();
    }

    public function routeHandler(): ?string
    {
        return $this->route->getHandler();
    }

    public function routeParams(): array
    {
        return $this->route->parameters();
    }

    // ── 认证 ──

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    // ── 行为判断 ──

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    public function isSafe(): bool
    {
        return in_array($this->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    public function isAjax(): bool
    {
        return $this->headers->isAjax();
    }

    public function ajax(): bool
    {
        return $this->isAjax();
    }

    public function expectsJson(): bool
    {
        return $this->headers->expectsJson();
    }

    // ── 客户端信息 ──

    public function ip(): string
    {
        return $this->server->getIp();
    }

    public function host(): string
    {
        return $this->server->getHost();
    }

    public function userAgent(): string
    {
        return $this->server->getUserAgent();
    }

    // ── 设置方法 ──

    public function setMethod(string $method): self
    {
        $this->server->set('REQUEST_METHOD', strtoupper($method));
        return $this;
    }

    public function setRequestUri(string $uri): self
    {
        $this->server->set('REQUEST_URI', $uri);
        return $this;
    }
}