<?php

declare(strict_types=1);

namespace Framework\Http\Request;

/**
 * 输入参数容器（Query / Post / JSON 的合并视图）
 *
 * 职责：参数存取、JSON 缓存、合并/移除操作
 */
class InputBag
{
    private array $query;
    private array $post;
    private ?array $jsonCache = null;

    public function __construct(array $query = [], array $post = [])
    {
        $this->query = $query;
        $this->post = $post;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }
        if ($this->jsonCache !== null && array_key_exists($key, $this->jsonCache)) {
            return $this->jsonCache[$key];
        }
        return $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post)
            || array_key_exists($key, $this->query)
            || ($this->jsonCache !== null && array_key_exists($key, $this->jsonCache));
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->jsonCache ?? []);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * 解析并缓存 JSON 请求体
     */
    public function parseJson(string $rawContent): ?array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        if ($rawContent === '') {
            return $this->jsonCache = [];
        }

        $decoded = json_decode($rawContent, true);
        return $this->jsonCache = is_array($decoded) ? $decoded : [];
    }

    /**
     * 获取已解析的 JSON 数据
     */
    public function json(): ?array
    {
        return $this->jsonCache;
    }

    /**
     * 设置参数
     */
    public function set(string $key, mixed $value): void
    {
        $this->post[$key] = $value;
        $this->jsonCache = null;
    }

    /**
     * 合并参数
     */
    public function merge(array $params): void
    {
        $this->post = array_merge($this->post, $params);
        $this->jsonCache = null;
    }

    /**
     * 移除参数
     */
    public function remove(string $key): void
    {
        unset($this->post[$key], $this->query[$key]);
        if ($this->jsonCache !== null) {
            unset($this->jsonCache[$key]);
        }
    }

    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function getPostParams(): array
    {
        return $this->post;
    }
}