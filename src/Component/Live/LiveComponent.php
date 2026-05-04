<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\Prop;
use Framework\Component\Live\Attribute\Computed;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\Session as SessionAttribute;
use Framework\Component\Live\Attribute\Cookie as CookieAttribute;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Http\Session;
use Framework\Http\Request;
use Framework\View\Base\Element;
use Framework\View\FragmentRegistry;

abstract class LiveComponent
{
    protected string $componentId;
    protected static array $idCounter = [];
    private array $actionCache = [];
    private static array $globalActionCache = [];
    private array $operations = [];
    private array $refreshFragments = [];
    private array $manualUpdates = [];
    private array $validationErrors = [];
    private ?string $stateChecksum = null;
    private ?string $lockedChecksum = null;
    protected array $routeParams = [];
    protected array $propValues = [];
    protected bool $mountCalled = false;
    private array $computedCache = [];
    private array $liveActions = [];

    public static function setGlobalActionCache(array $cache): void
    {
        self::$globalActionCache = $cache;
    }

    /**
     * 静态工厂方法：创建组件实例并注入 Props
     */
    public static function make(array $props = [], array $routeParams = []): static
    {
        $instance = new static();
        $instance->_invoke($routeParams);
        $instance->propValues = $props;
        return $instance;
    }

    public function __construct()
    {
        $shortClass = (new \ReflectionClass($this))->getShortName();
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
        if (!isset(self::$idCounter[$key])) self::$idCounter[$key] = 0;
        self::$idCounter[$key]++;
        $this->componentId = $key . '-' . self::$idCounter[$key];
    }

    /**
     * 设置组件名称/ID
     */
    public function named(string $name): static
    {
        $this->componentId = $name;
        return $this;
    }

    public function getComponentId(): string
    {
        return $this->componentId;
    }

    public function id(): string
    {
        return $this->componentId;
    }

    public function init(): void
    {
        $this->mount();
    }

    public function getManualUpdates(): array
    {
        return $this->manualUpdates;
    }

    public function getDataForFrontend(): array
    {
        return $this->getPublicProperties();
    }

    /**
     * mount() 只会在组件首次渲染时执行一次（SSR 阶段）
     */
    public function mount(): void {}

    /**
     * 注入 Props（从父组件传值或路由参数）
     */
    private function injectProps(): void
    {
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $propName = $prop->getName();
            $attrs = $prop->getAttributes(Prop::class);

            if (empty($attrs)) {
                continue;
            }

            $attr = $attrs[0]->newInstance();

            // 优先级：make()传入 > routeParams(fromRoute) > default
            $value = null;
            $found = false;

            if (isset($this->propValues[$propName])) {
                $value = $this->propValues[$propName];
                $found = true;
            } elseif ($attr->fromRoute !== null && isset($this->routeParams[$attr->fromRoute])) {
                $value = $this->routeParams[$attr->fromRoute];
                $found = true;
            } elseif ($attr->fromRoute !== null && isset($this->routeParams[$propName])) {
                $value = $this->routeParams[$propName];
                $found = true;
            } elseif ($attr->default !== null) {
                $value = $attr->default;
                $found = true;
            }

            if ($found) {
                $prop->setValue($this, $value);
            } elseif ($attr->required) {
                throw new \RuntimeException("Prop [{$propName}] is required but not provided for " . static::class);
            }
        }
    }

    public function toHtml(): string
    {
        $state = $this->serializeState();
        $publicProps = $this->getPublicProperties();

        $stateAttr = htmlspecialchars(json_encode($publicProps, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div data-live="%s" data-live-id="%s" data-state="%s" data-live-state="%s">%s</div>',
            static::class,
            $this->componentId,
            $stateAttr,
            $state,
            $this->render()
        );
    }

    /**
     * 前端请求接口，返回组件更新后的 HTML
     */
    public function handleEvent(string $event, array $data = []): string
    {
        $this->deserializeState($data['state'] ?? '');

        $this->fillPublicProperties($data['publicData'] ?? []);

        $this->callEvent($event);

        return $this->toHtml();
    }

    /**
     * 前端提交事件
     */
    public function handleSubmit(string $event, array $data = []): string
    {
        $this->deserializeState($data['state'] ?? '');

        $this->fillPublicProperties($data['publicData'] ?? []);

        $this->callEvent($event);

        return $this->toHtml();
    }

    public function handleAction(string $action, array $data = []): string
    {
        $this->deserializeState($data['state'] ?? '');

        $this->fillPublicProperties($data['publicData'] ?? []);

        $this->callAction($action, $data['params'] ?? []);

        return $this->toHtml();
    }

    /**
     * 组件操作（如 redirect, dispatch 等）
     */
    public function handleOperation(): array
    {
        return [
            'op' => $this->getOperations(),
        ];
    }

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

    public function mountHook(): void
    {
        // Mount hook
    }

    public function hydrate(): void
    {
        // Hydrate hook
    }

    public function dehydrate(): void
    {
        // Dehydrate hook
    }

    public function render(): Element
    {
        return new Element('div');
    }

    public function getPublicProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $data = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $name = $prop->getName();
            $value = $prop->getValue($this);

            if (is_resource($value)) {
                continue;
            }

            $data[$name] = $value;
        }

        return $data;
    }

    protected function getStateMeta(): array
    {
        $ref = new \ReflectionClass($this);
        $meta = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $sessionAttrs = $prop->getAttributes(SessionAttribute::class);
            if (!empty($sessionAttrs)) {
                $attr = $sessionAttrs[0]->newInstance();
                $meta[$prop->getName()] = [
                    'driver' => 'session',
                    'key' => $attr->key ?? $prop->getName(),
                ];
            }

            $cookieAttrs = $prop->getAttributes(CookieAttribute::class);
            if (!empty($cookieAttrs)) {
                $attr = $cookieAttrs[0]->newInstance();
                $meta[$prop->getName()] = [
                    'driver' => 'cookie',
                    'key' => $attr->key ?? $prop->getName(),
                    'minutes' => $attr->minutes,
                ];
            }
        }

        return $meta;
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    public function serializeState(): string
    {
        $data = [];
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties() as $prop) {
            if ($prop->isStatic()) continue;

            $internalProps = ['componentId', 'operations', 'refreshFragments', 'manualUpdates', 'actionCache', 'liveActions', 'mountCalled', 'stateChecksum', 'propValues', 'routeParams'];
            if (in_array($prop->getName(), $internalProps)) continue;

            if ($prop->isPublic()) continue;

            $value = $prop->getValue($this);

            if ($this->isSerializable($value)) {
                $data[$prop->getName()] = $value;
            }
        }

        $publicData = $this->getPublicProperties();
        $data['__checksum'] = $this->generateDataChecksum($publicData);

        // 额外存储 locked 属性的 checksum（用于校验非编辑属性）
        $editableProps = $this->frontendEditableProperties();
        $lockedData = [];
        foreach ($publicData as $propName => $value) {
            if (!in_array($propName, $editableProps, true)) {
                $lockedData[$propName] = $value;
            }
        }
        if (!empty($lockedData)) {
            $data['__locked_checksum'] = $this->generateDataChecksum($lockedData);
        }

        $serialized = serialize($data);
        $compressed = function_exists('gzcompress') ? gzcompress($serialized) : $serialized;

        $sig = hash_hmac('sha256', static::class . 'state' . $compressed, $this->liveSigningKey(), true);

        return base64_encode($sig . $compressed);
    }

    private function generateDataChecksum(array $data): string
    {
        $this->recursiveNormalize($data);
        return md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function recursiveNormalize(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveNormalize($value);
            } else {
                if ($value !== null) {
                    $value = (string)$value;
                }
            }
        }
    }

    protected function allowedStateProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $props = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            $stateAttrs = $prop->getAttributes(State::class);
            $propAttrs = $prop->getAttributes(Prop::class);
            $sessionAttrs = $prop->getAttributes(SessionAttribute::class);
            $cookieAttrs = $prop->getAttributes(CookieAttribute::class);
            if (!empty($stateAttrs) || !empty($propAttrs) || !empty($sessionAttrs) || !empty($cookieAttrs)) {
                $props[] = $prop->getName();
            }
        }
        return $props;
    }

    /**
     * 获取前端可编辑的属性列表
     */
    protected function frontendEditableProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $props = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            $attrs = $prop->getAttributes(State::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                if ($attr->frontendEditable) {
                    $props[] = $prop->getName();
                }
            }
        }
        return $props;
    }

    public function deserializeState(string $state): void
    {
        $decoded = base64_decode($state, true);
        if (!$decoded || strlen($decoded) < 32) return;

        $sig = substr($decoded, 0, 32);
        $compressed = substr($decoded, 32);

        $expectedSig = hash_hmac('sha256', static::class . 'state' . $compressed, $this->liveSigningKey(), true);
        if ($sig !== $expectedSig) {
            throw new \RuntimeException('Live component state signature verification failed. Possible tampering detected.');
        }

        $serialized = function_exists('gzuncompress') ? gzuncompress($compressed) : $compressed;
        $data = unserialize($serialized);

        if ($data === false && $serialized !== 'b:0;') {
            throw new \RuntimeException('Live component state deserialization failed.');
        }

        if (isset($data['__checksum'])) {
            $this->stateChecksum = $data['__checksum'];
            unset($data['__checksum']);
        }

        // 提取并存储 locked checksum（用于后续校验）
        if (isset($data['__locked_checksum'])) {
            $this->lockedChecksum = $data['__locked_checksum'];
            unset($data['__locked_checksum']);
        }

        foreach ($data as $key => $value) {
            $prop = new \ReflectionProperty($this, $key);
            if (!$prop->isStatic() && !$prop->isPublic()) {
                $prop->setValue($this, $value);
            }
        }

        foreach ($this->allowedStateProperties() as $propName) {
            if (!property_exists($this, $propName)) {
                continue;
            }
            $ref = new \ReflectionProperty($this, $propName);
            if ($ref->isStatic()) {
                continue;
            }

            $sessionAttrs = $ref->getAttributes(SessionAttribute::class);
            $cookieAttrs = $ref->getAttributes(CookieAttribute::class);

            if (!empty($sessionAttrs)) {
                $session = new Session();
                $sessionKey = 'live_component_' . static::class . '_' . $propName;
                $stored = $session->get($sessionKey);
                if ($stored !== null) {
                    $ref->setValue($this, $stored['value']);
                } else {
                    $value = $ref->getValue($this);
                    $session->set($sessionKey, [
                        'value' => $value,
                        'time' => time(),
                    ]);
                }
            } elseif (!empty($cookieAttrs)) {
                $attr = $cookieAttrs[0]->newInstance();
                $cookieName = 'live_component_' . static::class . '_' . $propName;
                if (isset($_COOKIE[$cookieName])) {
                    $value = json_decode($_COOKIE[$cookieName], true);
                    $ref->setValue($this, $value);
                } else {
                    $value = $ref->getValue($this);
                    $expire = time() + ($attr->minutes * 60);
                    setcookie($cookieName, json_encode($value), $expire, '/');
                }
            }
        }

        if (isset($data['_raw']) && is_array($data['_raw'])) {
            foreach ($data['_raw'] as $name => $value) {
                $ref = new \ReflectionProperty($this, $name);
                if (!$ref->isStatic() && $ref->isPublic()) {
                    $ref->setValue($this, $value);
                }
            }
        }

        $this->hydrate();
    }

    public function fillPublicProperties(array $data): void
    {
        if (isset($data['_raw']) && is_array($data['_raw'])) {
            $data = $data['_raw'];
        }

        $ref = new \ReflectionClass($this);
        $publicProps = $this->allowedStateProperties();
        $editableProps = $this->frontendEditableProperties();

        // 清理：只保留组件允许的属性，丢弃前端传来的"杂质"
        $cleanedData = [];
        foreach ($publicProps as $propName) {
            if (array_key_exists($propName, $data)) {
                $cleanedData[$propName] = $data[$propName];
            }
        }

        // 分级校验：
        // - #[State] 标记的属性：允许前端直接修改，不参与checksum校验
        // - 其他属性（#[Prop], #[Session], #[Cookie]）：严格checksum校验
        if ($this->lockedChecksum !== null) {
            // 提取非编辑（locked）属性
            $lockedData = [];
            foreach ($cleanedData as $propName => $value) {
                if (!in_array($propName, $editableProps, true)) {
                    $lockedData[$propName] = $value;
                }
            }

            // 校验 locked 数据是否被篡改
            if (!empty($lockedData)) {
                $currentLockedChecksum = $this->generateDataChecksum($lockedData);

                if (!hash_equals($this->lockedChecksum, $currentLockedChecksum)) {
                    if (config('app.debug')) {
                        error_log("Checksum mismatch! Locked data changed unexpectedly.");
                        error_log("Expected: {$this->lockedChecksum}, Got: {$currentLockedChecksum}");
                    }
                    throw new \RuntimeException('Live public state integrity check failed. Data tampering detected.');
                }
            }
        }

        foreach ($cleanedData as $name => $value) {
            if (in_array($name, $publicProps, true)) {
                $prop = $ref->getProperty($name);
                if (!$prop->isStatic()) {
                    $prop->setValue($this, $this->castParam($value, $prop->getType()));
                }
            }
        }
    }

    private function isSerializable(mixed $value): bool
    {
        if (is_scalar($value) || is_array($value) || is_null($value)) return true;
        if ($value instanceof \UnitEnum) return true;
        if ($value instanceof \DateTimeInterface) return true;
        return false;
    }

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
            } elseif (is_array($config) && isset($config['method'])) {
                $this->actionCache[$name] = $config['method'];
            }
        }

        return $this->actionCache;
    }

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

    public function addLiveAction(string $actionName, array $config): void
    {
        $this->liveActions[$actionName] = $config;
    }

    public function callAction(string $actionName, array $params = []): mixed
    {
        $params = $this->normalizeActionParams($actionName, $params);

        $actions = $this->getLiveActions();

        if (!isset($actions[$actionName])) {
            throw new \RuntimeException("LiveAction [{$actionName}] is not registered on " . static::class);
        }

        $methodName = $actions[$actionName];

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
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $args[] = null;
            }
        }

        return $this->$methodName(...$args);
    }

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

    public function operation(string $op, array $params = []): void
    {
        $newOp = array_merge(['op' => $op], $params);
        foreach ($this->operations as $existing) {
            if ($existing === $newOp) return;
        }
        $this->operations[] = $newOp;
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function redirect(string $url): void
    {
        $this->operation('redirect', ['url' => $url]);
    }

    public function refreshPage(): void
    {
        $this->operation('reload');
    }

    public function dispatchEvent(string $event, array $detail = []): void
    {
        $this->operation('dispatch', ['event' => $event, 'detail' => $detail]);
    }

    public function ux(string $component, string $id, string $action, array $data = []): void
    {
        $this->operation('ux:' . $component, array_merge(['id' => $id, 'action' => $action], $data));
    }

    public function openModal(string $id): void
    {
        $this->ux('modal', $id, 'open');
    }

    public function closeModal(string $id): void
    {
        $this->ux('modal', $id, 'close');
    }

    public function toggleAccordion(string $itemId, ?bool $open = null): void
    {
        $this->ux('accordion', $itemId, 'toggle', ['open' => $open]);
    }

    public function toast(string $message, string $type = 'success', int $duration = 3000, ?string $title = null): void
    {
        $this->ux('toast', '', 'show', [
            'message' => $message,
            'type' => $type,
            'duration' => $duration,
            'title' => $title,
        ]);
    }

    public function confirm(string $message, string $title = '确认', array $options = []): void
    {
        $this->operation('confirm', [
            'message' => $message,
            'title' => $title,
            ...$options
        ]);
    }

    public function loading(string $target = ''): void
    {
        $this->operation('loading', ['target' => $target ?: 'self']);
    }

    public function loadingEnd(string $target = ''): void
    {
        $this->operation('loading:end', ['target' => $target ?: 'self']);
    }

    public function validate(array $rules = [], array $data = []): bool
    {
        if (empty($data)) {
            $data = $this->getPublicProperties();
        }

        if (empty($rules)) {
            $this->validationErrors = [];
            return true;
        }

        $validator = \Framework\Validation\Validator::make($data, $rules);
        $passed = $validator->validate();

        $this->validationErrors = $validator->errors();
        return $passed;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * 刷新指定 Fragment（支持局部刷新模式）
     *
     * @param string $name Fragment 名称
     * @param string $mode 模式：'append'|'prepend'|'replace'（默认 replace）
     */
    public function refresh(string $name, string $mode = 'replace'): void
    {
        $this->refreshFragments[$name] = $mode;
    }

    /**
     * 获取 Fragment 刷新配置
     */
    public function getRefreshFragments(): array
    {
        return $this->refreshFragments;
    }

    /**
     * 获取已注册的 Fragment
     */
    public function getFragments(): array
    {
        return app()->make(FragmentRegistry::class)->getFragments($this->componentId);
    }

    /**
     * 获取指定 Fragment HTML
     */
    public function getFragment(string $name): string
    {
        return app()->make(FragmentRegistry::class)->getFragment($this->componentId, $name);
    }

    /**
     * 替换组件完整 HTML（通用局部刷新）
     */
    public function replace(): void
    {
        $this->operation('replace');
    }

    /**
     * 替换指定 CSS 选择器的元素
     */
    public function replaceElement(string $selector, ?string $content = null): void
    {
        $this->operation('replaceElement', [
            'selector' => $selector,
            'content' => $content,
        ]);
    }

    /**
     * 添加子元素到指定位置
     */
    public function addChild(string $selector, string $content, string $position = 'beforeend'): void
    {
        $this->operation('addChild', [
            'selector' => $selector,
            'content' => $content,
            'position' => $position,
        ]);
    }

    /**
     * 更新指定选择器元素的属性值
     */
    public function updateAttribute(string $selector, string $attribute, string $value): void
    {
        $this->operation('updateAttribute', [
            'selector' => $selector,
            'attribute' => $attribute,
            'value' => $value,
        ]);
    }

    /**
     * 获取组件所有公开属性的当前状态
     */
    public function getComponentState(): array
    {
        return $this->getPublicProperties();
    }

    /**
     * 手动触发另一个组件的方法
     * 格式: componentId.actionName
     */
    public function trigger(string $targetAction, array $params = []): void
    {
        $this->operation('trigger', [
            'target' => $targetAction,
            'params' => $params,
        ]);
    }

    /**
     * 手动注册 LiveAction
     *
     * @param string $name Action 名称
     * @param string|callable $method 方法名或回调函数
     * @param string $event 触发事件（默认 click）
     */
    public function registerAction(string $name, string|callable $method, string $event = 'click'): void
    {
        if (is_callable($method)) {
            $this->liveActions[$name] = $method;
        } else {
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

    protected function liveSigningKey(): string
    {
        $sessionToken = app()->make(Session::class)->getId() ?: 'guest';
        $appKey = config('app.key', 'default-key');
        return hash_hmac('sha256', $sessionToken, $appKey);
    }

    public function _invoke(array $params = []): void
    {
        $this->routeParams = $params;
        $this->injectProps();
        $this->mount();
    }

    public function _invokeAction(array $params = []): void
    {
        $this->routeParams = $params;
        $this->injectProps();
    }
}
