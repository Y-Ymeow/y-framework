<?php

declare(strict_types=1);

namespace Framework\Foundation;

class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $aliases = [];
    private array $resolving = [];

    public function set(string $id, mixed $value): void
    {
        $this->bindings[$id] = $value;
        unset($this->instances[$id]);
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $resolved = $this->resolve($id);

        if (isset($this->bindings[$id]) && $this->isSingletonBinding($id)) {
            $this->instances[$id] = $resolved;
        }

        return $resolved;
    }

    /**
     * 创建实例并传递额外参数
     * 
     * @param string $id 类名
     * @param array $extraParams 额外参数（会被注入到构造函数中）
     * @return mixed
     */
    public function makeWith(string $id, array $extraParams = []): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $resolved = $this->resolveWith($id, $extraParams);

        if (isset($this->bindings[$id]) && $this->isSingletonBinding($id)) {
            $this->instances[$id] = $resolved;
        }

        return $resolved;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    public function make(string $id, array $parameters = []): mixed
    {
        if (empty($parameters)) {
            return $this->get($id);
        }
        return $this->makeWith($id, $parameters);
    }

    public function singleton(string $id, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }

        if ($concrete instanceof \Closure) {
            $this->bindings[$id] = ['factory' => $concrete, 'singleton' => true];
        } elseif (is_string($concrete)) {
            $this->bindings[$id] = ['alias' => $concrete, 'singleton' => true];
        } else {
            $this->instances[$id] = $concrete;
            $this->bindings[$id] = ['value' => $concrete, 'singleton' => true];
        }
    }

    public function bind(string $id, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }

        if ($concrete instanceof \Closure) {
            $this->bindings[$id] = ['factory' => $concrete, 'singleton' => false];
        } elseif (is_string($concrete) && $concrete !== $id) {
            $this->bindings[$id] = ['alias' => $concrete, 'singleton' => false];
        } else {
            $this->bindings[$id] = ['value' => $concrete, 'singleton' => false];
        }
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        $this->bindings[$id] = ['value' => $instance, 'singleton' => true];
    }

    protected function resolve(string $id): mixed
    {
        $id = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            return $this->autowire($id);
        }

        $binding = $this->bindings[$id];

        if (is_array($binding)) {
            if (isset($binding['value'])) {
                return $binding['value'];
            }

            if (isset($binding['alias'])) {
                return $this->get($binding['alias']);
            }

            if (isset($binding['factory'])) {
                $result = ($binding['factory'])($this);

                if ($binding['singleton'] ?? false) {
                    $this->instances[$id] = $result;
                }

                return $result;
            }
        }

        if ($binding instanceof \Closure) {
            return $binding($this);
        }

        return $binding;
    }

    /**
     * 解析带额外参数的实例
     */
    protected function resolveWith(string $id, array $extraParams = []): mixed
    {
        $id = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        return $this->autowireWith($id, $extraParams);
    }

    protected function autowire(string $id): mixed
    {
        if (!class_exists($id)) {
            throw new \RuntimeException("Cannot resolve [{$id}]: class not found and not bound in container");
        }

        if (isset($this->resolving[$id])) {
            throw new \RuntimeException("Circular dependency detected while resolving [{$id}]");
        }

        $this->resolving[$id] = true;

        try {
            $ref = new \ReflectionClass($id);

            if (!$ref->isInstantiable()) {
                throw new \RuntimeException("Cannot instantiate [{$id}]: not instantiable");
            }

            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                return new $id();
            }

            $params = $this->resolveParameters($constructor->getParameters());
            return $ref->newInstanceArgs($params);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * 自动装配并注入额外参数
     */
    protected function autowireWith(string $id, array $extraParams = []): mixed
    {
        if (!class_exists($id)) {
            throw new \RuntimeException("Cannot resolve [{$id}]: class not found and not bound in container");
        }

        if (isset($this->resolving[$id])) {
            throw new \RuntimeException("Circular dependency detected while resolving [{$id}]");
        }

        $this->resolving[$id] = true;

        try {
            $ref = new \ReflectionClass($id);

            if (!$ref->isInstantiable()) {
                throw new \RuntimeException("Cannot instantiate [{$id}]: not instantiable");
            }

            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                return new $id();
            }

            $params = $this->resolveParametersWithExtras($constructor->getParameters(), $extraParams);
            return $ref->newInstanceArgs($params);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    protected function resolveParameters(array $parameters): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $args[] = $this->get($type->getName());
                } catch (\Throwable $e) {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        throw new \RuntimeException(
                            "Cannot resolve parameter \${$param->getName()} of type {$type->getName()}"
                        );
                    }
                }
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $args;
    }

    /**
     * 解析构造函数参数，支持额外参数注入
     */
    protected function resolveParametersWithExtras(array $parameters, array $extraParams = []): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $type = $param->getType();
            $paramName = $param->getName();

            // 1. 优先从额外参数中匹配（按参数名）
            if (array_key_exists($paramName, $extraParams)) {
                $args[] = $this->castParam($extraParams[$paramName], $type);
                continue;
            }

            // 2. 尝试从容器解析类型化依赖
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $args[] = $this->get($type->getName());
                    continue;
                } catch (\Throwable $e) {
                    // 容器解析失败，继续尝试其他方式
                }
            }

            // 3. 使用默认值
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $args;
    }

    /**
     * 参数类型转换
     */
    protected function castParam(mixed $value, ?\ReflectionType $type): mixed
    {
        if ($type === null || !($type instanceof \ReflectionNamedType)) return $value;

        return match ($type->getName()) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string)$value,
            default => $value,
        };
    }

    protected function isSingletonBinding(string $id): bool
    {
        if (!isset($this->bindings[$id])) {
            return false;
        }

        $binding = $this->bindings[$id];

        if (is_array($binding)) {
            return $binding['singleton'] ?? false;
        }

        return false;
    }
}
