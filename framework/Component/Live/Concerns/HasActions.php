<?php

declare(strict_types=1);

namespace Framework\Component\Live\Concerns;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LiveListener;

/**
 * @mixin \Framework\Component\Live\LiveComponent
 */
trait HasActions
{
    private array $actionCache = [];
    private static array $globalActionCache = [];
    private array $liveActions = [];

    public static function setGlobalActionCache(array $cache): void
    {
        self::$globalActionCache = $cache;
    }

    /**
     * 获取所有注册的 LiveAction 映射
     */
    public function getLiveActions(): array
    {
        if (isset(self::$globalActionCache[static::class])) {
            return self::$globalActionCache[static::class];
        }

        if (!empty($this->actionCache)) return $this->actionCache;

        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(LiveAction::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                $name = $attr->name ?? $method->getName();
                $this->actionCache[$name] = $method->getName();
            }

            $pollAttrs = $method->getAttributes(\Framework\Component\Live\Attribute\LivePoll::class);
            if (!empty($pollAttrs)) {
                $name = $method->getName();
                if (!isset($this->actionCache[$name])) {
                    $this->actionCache[$name] = $method->getName();
                }
            }
        }

        foreach ($this->liveActions as $name => $config) {
            if (is_string($config)) {
                $this->actionCache[$name] = $config;
            } elseif (is_array($config)) {
                // 区分配置数组 ['method' => 'xxx'] 和类方法引用 ['ClassName', 'methodName']
                if (isset($config['method'])) {
                    $this->actionCache[$name] = $config['method'];
                } elseif (count($config) === 2 && is_string($config[0]) && is_string($config[1])) {
                    $this->actionCache[$name] = $config;
                }
            }
        }

        if (!isset($this->actionCache['__updateProperty'])) {
            $this->actionCache['__updateProperty'] = '__updateProperty';
        }

        if (!isset($this->actionCache['__refresh'])) {
            $this->actionCache['__refresh'] = '__refresh';
        }

        return $this->actionCache;
    }

    /**
     * 获取指定 Action 的配置
     */
    public function getLiveActionConfig(string $actionName): ?array
    {
        if (isset($this->liveActions[$actionName])) {
            $config = $this->liveActions[$actionName];
            if (is_string($config)) {
                return ['method' => $config, 'event' => 'click'];
            }
            if (is_array($config)) {
                return array_merge(['event' => 'click'], $config);
            }
        }

        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(LiveAction::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                $name = $attr->name ?? $method->getName();
                if ($name === $actionName) {
                    return [
                        'method' => $method->getName(),
                        'event' => $attr->event ?? 'click',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * 手动添加 LiveAction
     */
    public function addLiveAction(string $actionName, array $config): void
    {
        $this->liveActions[$actionName] = $config;
    }

    /**
     * 手动注册 LiveAction
     *
     * @param string $name Action 名称
     * @param string|array $method 方法名或 ['ClassName', 'methodName'] 外部类引用
     * @param string $event 触发事件（默认 click）
     */
    public function registerAction(string $name, string|array $method, string $event = 'click'): void
    {
        if (is_array($method) && count($method) === 2 && is_string($method[0]) && is_string($method[1])) {
            // ['ClassName', 'methodName'] 外部类方法引用
            $this->liveActions[$name] = $method;
        } elseif (is_string($method)) {
            $this->liveActions[$name] = [
                'method' => $method,
                'event' => $event,
            ];
        }
    }

    /**
     * 批量注册 Actions
     *
     * @param array $actions ['actionName' => 'methodName'] 或 ['actionName' => ['method' => 'methodName', 'event' => 'click']]
     */
    public function registerActions(array $actions): void
    {
        foreach ($actions as $name => $config) {
            if (is_string($config)) {
                $this->liveActions[$name] = $config;
            } elseif (is_array($config)) {
                $this->liveActions[$name] = array_merge(['event' => 'click'], $config);
            }
        }
    }

    /**
     * 调用指定的 Action
     */
    public function callAction(string $actionName, array $params = []): mixed
    {
        $params = $this->normalizeActionParams($actionName, $params);

        $actions = $this->getLiveActions();

        if (!isset($actions[$actionName])) {
            throw new \RuntimeException("LiveAction [{$actionName}] is not registered on " . static::class);
        }

        $handler = $actions[$actionName];

        // 支持外部类方法引用 ['ClassName', 'methodName']
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            [$className, $methodName] = $handler;

            if (!class_exists($className)) {
                throw new \RuntimeException("Action class [{$className}] not found");
            }

            if (!method_exists($className, $methodName)) {
                throw new \RuntimeException("Action method [{$className}::{$methodName}] not found");
            }

            // 校验 #[LiveAction] 标记
            $ref = new \ReflectionMethod($className, $methodName);
            $attrs = $ref->getAttributes(LiveAction::class);
            if (empty($attrs)) {
                throw new \RuntimeException("Method [{$className}::{$methodName}] is not marked with #[LiveAction]");
            }

            return $className::$methodName($this, $params);
        }

        // 原有 string 方法名逻辑（本组件方法）
        $methodName = $handler;

        if (!method_exists($this, $methodName)) {
            throw new \RuntimeException("LiveAction [{$actionName}] method [{$methodName}] not found on " . static::class);
        }

        $ref = new \ReflectionMethod($this, $methodName);
        $args = [];
        $parameters = $ref->getParameters();

        if (count($parameters) === 1) {
            $param = $parameters[0];
            $name = $param->getName();
            $type = $param->getType();
            if ($name === 'params' && $type && $type->getName() === 'array') {
                $args[] = $params;
                return $this->$methodName(...$args);
            }
        }

        foreach ($parameters as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (isset($params[$name])) {
                $args[] = $this->castParam($params[$name], $type);
            } elseif (array_key_exists($param->getPosition(), $params)) {
                $args[] = $this->castParam($params[$param->getPosition()], $type);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $args[] = null;
            }
        }

        return $this->$methodName(...$args);
    }

    /**
     * 规范化 Action 参数（按配置的命名参数映射）
     */
    private function normalizeActionParams(string $actionName, array $params): array
    {
        $config = $this->getLiveActionConfig($actionName);
        if ($config && isset($config['params']) && is_array($config['params'])) {
            $normalized = [];
            foreach ($config['params'] as $index => $paramName) {
                if (isset($params[$index])) {
                    $normalized[$paramName] = $params[$index];
                }
            }
            return $normalized;
        }
        return $params;
    }

    /**
     * 类型转换参数
     */
    private function castParam(mixed $value, ?\ReflectionType $type): mixed
    {
        if ($value === null) return null;
        if (!$type) return $value;

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            return match ($typeName) {
                'int' => (int)$value,
                'float' => (float)$value,
                'bool' => (bool)$value,
                'string' => (string)$value,
                'array' => is_array($value) ? $value : [$value],
                'object' => is_object($value) ? $value : (object)$value,
                default => $value,
            };
        }

        return $value;
    }

    /**
     * 获取所有 LiveListener 注册
     */
    private function getLiveListeners(): array
    {
        $ref = new \ReflectionClass($this);
        $listeners = [];

        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(LiveListener::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                $listeners[] = [
                    'event' => $attr->event,
                    'method' => $method->getName(),
                ];
            }
        }

        return $listeners;
    }

    /**
     * 触发事件监听器
     */
    protected function callEvent(string $event): void
    {
        $eventName = str_replace('live:', '', $event);
        $listeners = $this->getLiveListeners();

        foreach ($listeners as $listener) {
            if ($listener['event'] === $eventName) {
                $method = $listener['method'];
                $this->$method();
            }
        }
    }

    /**
     * 前端请求事件入口
     */
    public function handleEvent(string $event, array $data = []): string
    {
        $this->deserializeState($data['state'] ?? '');
        $this->fillPublicProperties($data['publicData'] ?? []);
        $this->callEvent($event);
        return $this->toHtml();
    }

    /**
     * 前端请求 Action 入口
     */
    public function handleAction(string $action, array $data = []): string
    {
        $this->deserializeState($data['state'] ?? '');
        $this->fillPublicProperties($data['publicData'] ?? []);
        $this->callAction($action, $data['params'] ?? []);
        return $this->toHtml();
    }
}