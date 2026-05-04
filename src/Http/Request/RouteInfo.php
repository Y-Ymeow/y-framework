<?php

declare(strict_types=1);

namespace Framework\Http\Request;

/**
 * 路由信息值对象
 */
class RouteInfo
{
    private ?string $name = null;
    private ?string $handler = null;
    private array $params = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getActionName(): ?string
    {
        return $this->handler;
    }

    public function getHandler(): ?string
    {
        return $this->handler;
    }

    public function parameters(): array
    {
        return $this->params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    public function set(string $name, string $handler, array $params = []): void
    {
        $this->name = $name;
        $this->handler = $handler;
        $this->params = $params;
    }

    public function isResolved(): bool
    {
        return $this->name !== null;
    }

    /**
     * 作为对象返回（兼容旧版 Request::route() 返回 object 的行为）
     */
    public function toObject(): object
    {
        return new class($this->name, $this->handler, $this->params) {
            public function __construct(
                private ?string $name,
                private ?string $handler,
                private array $params
            ) {}
            public function getName(): ?string { return $this->name; }
            public function getActionName(): ?string { return $this->handler; }
            public function getHandler(): ?string { return $this->handler; }
            public function parameters(): array { return $this->params; }
        };
    }
}