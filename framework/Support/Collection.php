<?php

declare(strict_types=1);

namespace Framework\Support;

/**
 * Collection 类 - 包装数据库查询返回的 array 为对象
 * 提供类似 Laravel 的 Model 属性访问方式
 */
class Collection implements \ArrayAccess, \Iterator, \Countable
{
    private array $items;
    private int $position = 0;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * 从单个 array 创建对象
     */
    public static function make(array $data): ?self
    {
        if (empty($data)) {
            return null;
        }

        return new self($data);
    }

    /**
     * 从多个 array 创建集合
     */
    public static function makeMany(array $items): self
    {
        return new self($items);
    }

    /**
     * 魔术方法：访问属性
     */
    public function __get(string $name): mixed
    {
        return $this->items[$name] ?? null;
    }

    /**
     * 魔术方法：设置属性
     */
    public function __set(string $name, mixed $value): void
    {
        $this->items[$name] = $value;
    }

    /**
     * 魔术方法：检查属性是否存在
     */
    public function __isset(string $name): bool
    {
        return isset($this->items[$name]);
    }

    /**
     * 魔术方法：删除属性
     */
    public function __unset(string $name): void
    {
        unset($this->items[$name]);
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * 获取所有字段
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * 获取指定字段
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * 设置字段值
     */
    public function set(string $key, mixed $value): self
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * 删除字段
     */
    public function forget(string $key): self
    {
        unset($this->items[$key]);
        return $this;
    }

    /**
     * 判断是否有指定字段
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    // ArrayAccess 接口实现

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // Iterator 接口实现

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->items[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    // Countable 接口实现

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * 判断是否为空
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * 判断是否不为空
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->items);
    }

    /**
     * 获取第一个元素
     */
    public function first(): mixed
    {
        return reset($this->items);
    }

    /**
     * 获取最后一个元素
     */
    public function last(): mixed
    {
        return end($this->items);
    }

    /**
     * 过滤
     */
    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }

        return new self(array_filter($this->items, $callback));
    }

    /**
     * 映射
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    /**
     * 合并
     */
    public function merge(array $items): self
    {
        return new self(array_merge($this->items, $items));
    }

    /**
     * 只保留指定字段
     */
    public function only(array $keys): self
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->items)) {
                $result[$key] = $this->items[$key];
            }
        }
        return new self($result);
    }

    /**
     * 排除指定字段
     */
    public function except(array $keys): self
    {
        $result = $this->items;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return new self($result);
    }
}
