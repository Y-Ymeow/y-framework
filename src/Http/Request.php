<?php

declare(strict_types=1);

namespace Framework\Http;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
    private SymfonyRequest $sfRequest;
    private ?\Closure $routeCallback = null;
    private array $routeParams = [];
    private ?string $routeName = null;
    private ?string $routeHandler = null;

    public function __construct(?SymfonyRequest $sfRequest = null)
    {
        $this->sfRequest = $sfRequest ?? SymfonyRequest::createFromGlobals();
    }

    public function method(): string
    {
        return $this->sfRequest->getMethod();
    }

    public function path(): string
    {
        $path = $this->sfRequest->getRequestUri();
        $path = parse_url($path, PHP_URL_PATH);
        return $path ?: '/';
    }

    public function url(): string
    {
        return $this->sfRequest->getUri();
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->isJson()) {
            $data = $this->json();
            if ($data !== null && array_key_exists($key, $data)) {
                return $data[$key];
            }
        }
        
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->sfRequest->query->get($key, $default);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->sfRequest->request->get($key, $default);
    }

    public function json(): ?array
    {
        return json_decode($this->sfRequest->getContent(), true);
    }

    public function header(string $key, ?string $default = null): ?string
    {
        return $this->sfRequest->headers->get($key, $default);
    }

    public function all(): array
    {
        return array_merge(
            $this->sfRequest->query->all(),
            $this->sfRequest->request->all(),
            $this->json() ?? []
        );
    }

    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->sfRequest->cookies->get($key, $default);
    }

    public function ip(): string
    {
        return $this->sfRequest->getClientIp() ?? '127.0.0.1';
    }

    public function host(): string
    {
        return $this->sfRequest->getHost() ?? 'localhost';
    }

    public function isMethod(string $method): bool
    {
        return $this->sfRequest->isMethod($method);
    }

    public function isAjax(): bool
    {
        return $this->sfRequest->isXmlHttpRequest();
    }

    public function ajax(): bool
    {
        return $this->isAjax();
    }

    public function getRequestUri(): string
    {
        return $this->sfRequest->getRequestUri();
    }

    public function getUri(): string
    {
        return $this->sfRequest->getUri();
    }

    public function getMethod(): string
    {
        return $this->sfRequest->getMethod();
    }

    public function isJson(): bool
    {
        $ct = $this->sfRequest->headers->get('Content-Type', '');
        return str_contains($ct, 'application/json');
    }

    public function expectsJson(): bool
    {
        $accept = $this->sfRequest->headers->get('Accept', '');
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    public function file(string $key): ?\Symfony\Component\HttpFoundation\File\UploadedFile
    {
        return $this->sfRequest->files->get($key);
    }

    public function setRoute(string $name, string $handler, array $params = []): self
    {
        $this->routeName = $name;
        $this->routeHandler = $handler;
        $this->routeParams = $params;
        return $this;
    }

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

    public function routeName(): ?string { return $this->routeName; }
    public function routeHandler(): ?string { return $this->routeHandler; }
    public function routeParams(): array { return $this->routeParams; }

    public function getSfRequest(): SymfonyRequest
    {
        return $this->sfRequest;
    }

    public static function createFromGlobals(): self
    {
        return new self(SymfonyRequest::createFromGlobals());
    }

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): self
    {
        return new self(SymfonyRequest::create($uri, $method, $parameters, $cookies, $files, $server, $content));
    }
}
