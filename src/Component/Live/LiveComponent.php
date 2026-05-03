<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\Prop;
use Framework\Component\Live\Attribute\Computed;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Http\Session;
use Framework\Http\Request;
use Framework\View\Base\Element;

abstract class LiveComponent
{
    protected string $componentId;
    protected static array $idCounter = [];
    private array $actionCache = [];
    private static array $globalActionCache = [];
    private array $operations = [];
    private array $refreshFragments = []; // [name => mode]
    private array $manualUpdates = []; // [componentId => patches]
    private array $validationErrors = [];
    private ?string $stateChecksum = null;
    /**
     * 路由参数（由 LiveComponentResolver 通过容器注入）
     */
    protected array $routeParams = [];

    /**
     * 手动注册的 LiveActions
     * 格式: ['actionName' => 'methodName'] 或 ['actionName' => ['method' => 'methodName', 'event' => 'click']]
     */
    protected array $liveActions = [];

    /**
     * 标记 mount() 是否已被调用
     * 用于防止重复初始化
     */
    private bool $mountCalled = false;

    public static function setGlobalActionCache(array $cache): void
    {
        self::$globalActionCache = $cache;
    }

    /**
     * 构造函数
     * 
     * @param array $routeParams 路由参数（由 LiveComponentResolver 通过容器注入）
     */
    public function __construct(array $routeParams = [])
    {
        $this->routeParams = $routeParams;

        // 初始化组件 ID
        $shortClass = (new \ReflectionClass($this))->getShortName();
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
        if (!isset(self::$idCounter[$key])) self::$idCounter[$key] = 0;
        self::$idCounter[$key]++;
        $this->componentId = $key . '-' . self::$idCounter[$key];

        // 执行 boot 周期
        $this->boot();

        // 处理 Session/Cookie 属性恢复
        $this->syncPersistentAttributes();

        // 调用 mount() 生命周期钩子（仅在首次实例化时）
        if (!$this->mountCalled) {
            $this->mountCalled = true;
            $this->mount();
        }
    }

    /**
     * 同步具有 #[Session] 或 #[Cookie] 属性的字段
     */
    protected function syncPersistentAttributes(): void
    {
        $ref = new \ReflectionClass($this);
        foreach ($ref->getProperties() as $prop) {
            // 处理 Session
            $sessionAttrs = $prop->getAttributes(\Framework\Component\Live\Attribute\Session::class);
            if (!empty($sessionAttrs)) {
                $attr = $sessionAttrs[0]->newInstance();
                $key = $attr->key ?: (static::class . '.' . $prop->getName());
                $session = new \Framework\Http\Session();
                if ($session->has($key)) {
                    $prop->setValue($this, $session->get($key));
                }
            }

            // 处理 Cookie
            $cookieAttrs = $prop->getAttributes(\Framework\Component\Live\Attribute\Cookie::class);
            if (!empty($cookieAttrs)) {
                $attr = $cookieAttrs[0]->newInstance();
                $key = $attr->key ?: (static::class . '.' . $prop->getName());
                $request = app()->make(\Framework\Http\Request::class);
                $value = $request->cookie($key);
                if ($value !== null) {
                    // 尝试反序列化（简单处理标量）
                    $prop->setValue($this, $this->castParam($value, $prop->getType()));
                }
            }
        }
    }

    /**
     * 生命周期：组件实例创建时触发
     */
    public function boot(): void {}

    /**
     * 生命周期：组件初始化时触发（数据加载、默认值设置等）
     * 在构造函数末尾自动调用，仅调用一次
     */
    public function mount(): void {}

    /**
     * 生命周期：在状态从请求恢复（Hydration）完成后触发
     */
    public function hydrate(): void {}

    /**
     * 生命周期：在状态序列化发往前端（Dehydration）开始前触发
     */
    public function dehydrate(): void {}

    /**
     * 获取路由参数值
     *
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     *
     * @live-example $this->param('id', 0)
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * 获取所有路由参数
     * @return array
     * @live-example $this->params()  // → ['id' => '123', 'slug' => 'my-post']
     */
    public function params(): array
    {
        return $this->routeParams;
    }

    /**
     * 判断是否存在指定路由参数
     * @param string $key 参数名
     * @return bool
     */
    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->routeParams);
    }

    /**
     * 设置路由参数（用于子请求或测试）
     * @param array $params 参数数组
     * @return static
     */
    public function setRouteParams(array $params): static
    {
        $this->routeParams = array_merge($this->routeParams, $params);
        return $this;
    }

    /**
     * 生命周期钩子：当任何公开属性更新后触发
     */
    public function updated(string $name, mixed $value): void 
    {
        // 自动保存到 Session/Cookie
        $ref = new \ReflectionClass($this);
        if (!$ref->hasProperty($name)) return;
        $prop = $ref->getProperty($name);

        // 处理 Session 保存
        $sessionAttrs = $prop->getAttributes(\Framework\Component\Live\Attribute\Session::class);
        if (!empty($sessionAttrs)) {
            $attr = $sessionAttrs[0]->newInstance();
            $key = $attr->key ?: (static::class . '.' . $prop->getName());
            $session = new \Framework\Http\Session();
            $session->set($key, $value);
        }

        // 处理 Cookie 保存
        $cookieAttrs = $prop->getAttributes(\Framework\Component\Live\Attribute\Cookie::class);
        if (!empty($cookieAttrs)) {
            $attr = $cookieAttrs[0]->newInstance();
            $key = $attr->key ?: (static::class . '.' . $prop->getName());
            // Cookie 的实际设置需要通过 Response，这里我们先简单模拟或通过 header 设置
            // 注意：这在 AJAX 请求中可能需要特殊处理
            setcookie($key, (string)$value, time() + ($attr->minutes * 60), $attr->path ?? '/', $attr->domain ?? '', $attr->secure ?? false, $attr->httpOnly);
        }
    }

    abstract public function render();

    /**
     * 内置属性同步方法，支持 data-live-model
     */
    #[LiveAction]
    public function __updateProperty(array $params): void
    {
        $property = $params['property'] ?? null;
        $value = $params['value'] ?? null;

        if ($property === null) {
            return;
        }

        // 检查属性是否存在且允许访问
        $ref = new \ReflectionClass($this);
        if (!$ref->hasProperty($property)) {
            return;
        }

        $prop = $ref->getProperty($property);

        // 只有非静态属性可以同步
        if ($prop->isStatic()) {
            return;
        }

        // 执行更新
        $oldValue = $prop->isInitialized($this) ? $prop->getValue($this) : null;

        // 类型转换
        $type = $prop->getType();
        $castedValue = $this->castParam($value, $type);

        $prop->setValue($this, $castedValue);

        // 调用生命周期钩子: updatedProperty($value, $oldValue)
        $hookName = 'updated' . ucfirst($property);
        if (is_callable([$this, $hookName])) {
            $this->{$hookName}($castedValue, $oldValue);
        }

        // 通用钩子: updated($property, $value)
        $this->updated($property, $castedValue);

        // 默认触发一次刷新，以确保 UI 同步
        $this->refresh();
    }

    public function getComponentId(): string
    {
        return $this->componentId;
    }

    /**
     * 设置组件 ID （用于唯一标识组件） 方便在更新时引用
     * @param string $id 组件 ID
     * @return self
     */
    public function named(string $id): self
    {
        $this->componentId = $id;
        return $this;
    }

    public function toHtml(bool $onlyFragment = false): string
    {
        $this->dehydrate();

        $content = $this->render();

        // 如果返回的是 Response，转换为字符串
        if ($content instanceof \Framework\Http\Response) {
            return $content->getContent();
        }

        // 如果返回的是字符串且包含 <!DOCTYPE，拒绝（安全限制）
        if (is_string($content) && str_contains($content, '<!DOCTYPE')) {
            throw new \LogicException(
                'LiveComponent cannot return HTML document string. ' .
                'Return Element or UXComponent instead.'
            );
        }

        // 否则包装在 Element 中
        if (!($content instanceof Element)) {
            $content = Element::make('div')->child($content);
        }

        $content->attr('data-live', static::class);
        $content->attr('data-live-id', $this->componentId);
        $content->attr('data-live-state', $this->serializeState());
        $content->attr('data-state', json_encode($this->getDataForFrontend(), JSON_UNESCAPED_UNICODE));

        $listeners = $this->getLiveListeners();
        if (!empty($listeners)) {
            $content->attr('data-live-listeners', implode(',', array_keys($listeners)));
        }

        $polls = $this->getLivePolls();
        if (!empty($polls)) {
            $content->attr('data-poll', json_encode($polls, JSON_UNESCAPED_UNICODE));
        }

        return $content->render($onlyFragment);
    }

    /**
     * 检测是否为嵌套调用
     *
     * 用于安全限制：只有顶层 Page 组件才能返回 Document
     */
    private function isNestedCall(): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($trace as $frame) {
            // 如果调用栈中有其他 LiveComponent::toHtml，说明是嵌套
            if (isset($frame['class']) &&
                $frame['class'] !== static::class &&
                is_subclass_of($frame['class'], self::class) &&
                $frame['function'] === 'toHtml') {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取组件定义的监听器
     */
    public function getLiveListeners(): array
    {
        $listeners = [];
        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(LiveListener::class);
            foreach ($attrs as $attr) {
                $listener = $attr->newInstance();
                $listeners[$listener->event] = $method->getName();
            }
        }
        return $listeners;
    }

    /**
     * 获取组件定义的轮询方法
     *
     * @return array<string, array{interval: int, immediate: bool, condition: string}>
     */
    public function getLivePolls(): array
    {
        $polls = [];
        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(\Framework\Component\Live\Attribute\LivePoll::class);
            foreach ($attrs as $attr) {
                $poll = $attr->newInstance();
                $polls[$method->getName()] = [
                    'interval' => $poll->interval,
                    'immediate' => $poll->immediate,
                    'condition' => $poll->condition,
                ];
            }
        }
        return $polls;
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * 序列化状态
     * 优化：只序列化非公开属性，公开属性由前端 JSON 维护，减少数据冗余
     */
    public function serializeState(): string
    {
        $data = [];
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties() as $prop) {
            if ($prop->isStatic()) continue;

            $internalProps = ['componentId', 'operations', 'refreshFragments', 'manualUpdates', 'actionCache', 'liveActions'];
            if (in_array($prop->getName(), $internalProps)) continue;

            if ($prop->isPublic()) continue;

            $value = $prop->getValue($this);

            if ($this->isSerializable($value)) {
                $data[$prop->getName()] = $value;
            }
        }

        // 安全增强：记录公开属性的指纹，防止前端篡改 _data
        $publicData = $this->getPublicProperties();
        $data['__checksum'] = $this->generateDataChecksum($publicData);

        $serialized = serialize($data);
        $compressed = function_exists('gzcompress') ? gzcompress($serialized) : $serialized;

        $sig = hash_hmac('sha256', static::class . 'state' . $compressed, $this->liveSigningKey(), true);

        return base64_encode($sig . $compressed);
    }

    /**
     * 生成一致的数据指纹
     */
    private function generateDataChecksum(array $data): string
    {
        // 递归排序和规范化（转为字符串），确保 JSON 字符串一致性并消除类型差异
        $this->recursiveNormalize($data);

        return md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * 递归规范化数组：排序并转为字符串
     */
    private function recursiveNormalize(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveNormalize($value);
            } else {
                // 将所有非数组值转为字符串，以抹平 1 vs "1" 的差异
                if ($value !== null) {
                    $value = (string)$value;
                }
            }
        }
    }

    /**
     * 获取组件允许从 state 恢复的属性白名单（公开属性）
     * 子类可覆盖此方法以限制可被覆盖的属性
     */
    protected function allowedStateProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $props = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if (!$prop->isStatic()) {
                $props[] = $prop->getName();
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

        if (!hash_equals($expectedSig, $sig)) {
            throw new \RuntimeException('Live state signature is invalid.');
        }

        $serialized = function_exists('gzuncompress') ? @gzuncompress($compressed) : $compressed;
        if ($serialized === false) {
            $serialized = $compressed;
        }

        $data = @unserialize($serialized, ['allowed_classes' => false]);
        if (!is_array($data)) return;

        if (isset($data['__checksum'])) {
            $this->stateChecksum = $data['__checksum'];
            unset($data['__checksum']);
        }

        $ref = new \ReflectionClass($this);
        foreach ($data as $name => $value) {
            if ($ref->hasProperty($name)) {
                $prop = $ref->getProperty($name);
                // 恢复私有/受保护属性
                if (!$prop->isPublic()) {
                    $prop->setValue($this, $value);
                }
            }
        }

        // 属性恢复后执行 hydrate 钩子
        $this->hydrate();
    }

    /**
     * 将前端传来的公开属性值填充到组件中
     */
    public function fillPublicProperties(array $data): void
    {
        if (isset($data['_raw']) && is_array($data['_raw'])) {
            $data = $data['_raw'];
        }

        $ref = new \ReflectionClass($this);
        $publicProps = $this->allowedStateProperties();
        
        // 校验完整性：确保前端传回的公开数据与服务端上次签发的快照一致
        if ($this->stateChecksum !== null) {
            // 关键：只提取 PHP 类中存在的公开属性进行校验，忽略所有前端“杂质”
            $dataToVerify = [];
            foreach ($publicProps as $propName) {
                if (array_key_exists($propName, $data)) {
                    $dataToVerify[$propName] = $data[$propName];
                }
            }
            
            $currentChecksum = $this->generateDataChecksum($dataToVerify);
            if (!hash_equals($this->stateChecksum, $currentChecksum)) {
                if (config('app.debug')) {
                    error_log("Checksum mismatch! Expected: {$this->stateChecksum}, Got: {$currentChecksum}");
                    error_log("Verified Data: " . json_encode($dataToVerify));
                }
                throw new \RuntimeException('Live public state integrity check failed. Data tampering detected.');
            }
        }

        // 填充属性
        foreach ($data as $name => $value) {
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

    public function signedAction(string $actionName, array $params = []): string
    {
        return $this->encodeActionParams($actionName, $params);
    }

    protected function encodeActionParams(string $actionName, array $params): string
    {
        $payload = [
            '_payload' => $params,
            '_signature' => $this->signPayload([
                'component' => static::class,
                'type' => 'action-params',
                'action' => $actionName,
                'params' => $params,
            ]),
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeActionParams(string $actionName, array $params): array
    {
        if (!isset($params['_payload']) && !isset($params['_signature'])) {
            return $params;
        }

        $payload = $params['_payload'] ?? null;
        $signature = $params['_signature'] ?? null;

        if (!is_array($payload) || !is_string($signature) || $signature === '') {
            throw new \RuntimeException('Live action params signature payload is invalid.');
        }

        $expected = $this->signPayload([
            'component' => static::class,
            'type' => 'action-params',
            'action' => $actionName,
            'params' => $payload,
        ]);

        if (!hash_equals($expected, $signature)) {
            throw new \RuntimeException('Live action params signature is invalid.');
        }

        return $payload;
    }

    private function castParam(mixed $value, ?\ReflectionType $type): mixed
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

    public function getPublicProperties(): array
    {
        $data = [];
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) continue;
            $data[$prop->getName()] = $prop->getValue($this);
        }

        return $data;
    }

    public function getDataForFrontend(): array
    {
        return $this->getPublicProperties();
    }

    public function validate(): array
    {
        $errors = [];
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties() as $prop) {
            $attrs = $prop->getAttributes(\Framework\Component\Live\Attribute\Rule::class);
            if (empty($attrs)) continue;

            $rule = $attrs[0]->newInstance();
            $value = $prop->getValue($this);
            $name = $prop->getName();

            $rules = explode('|', $rule->rules);

            foreach ($rules as $r) {
                $error = $this->applyRule($name, $value, $r);
                if ($error) $errors[$name] = $error;
            }
        }

        return $errors;
    }

    private function applyRule(string $name, mixed $value, string $rule): ?string
    {
        return match ($rule) {
            'required' => empty($value) && $value !== '0' ? "{$name} 是必填项" : null,
            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) ? "{$name} 格式不正确" : null,
            default => null,
        };
    }

    public function emit(string $event, mixed $data = null): void
    {
        LiveEventBus::recordEmittedEvent($event, $data);
    }

    public function updateComponent(string $componentId, array $patches = []): void
    {
        $this->manualUpdates[$componentId] = array_merge(
            $this->manualUpdates[$componentId] ?? [],
            $patches
        );
    }

    public function getManualUpdates(): array
    {
        return $this->manualUpdates;
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
        $this->operation('ux:toast', [
            'message' => $message,
            'type' => $type,
            'duration' => $duration,
            'title' => $title
        ]);
    }

    /**
     * 触发确认对话框（前端显示）
     *
     * @param string $message 确认消息
     * @param string $title 对话框标题（可选）
     * @param array $options 额外选项
     * @return bool 仅作为操作标记，实际结果由前端处理
     */
    public function confirm(string $message, string $title = '确认', array $options = []): void
    {
        $this->operation('confirm', [
            'message' => $message,
            'title' => $title,
            ...$options
        ]);
    }

    /**
     * 触发局部加载状态
     *
     * @param string $target 加载目标（CSS 选择器或组件 ID）
     */
    public function loading(string $target = ''): void
    {
        $this->operation('loading', ['target' => $target ?: 'self']);
    }

    /**
     * 结束加载状态
     *
     * @param string $target 结束加载的目标
     */
    public function loadingEnd(string $target = ''): void
    {
        $this->operation('loading:end', ['target' => $target ?: 'self']);
    }

    /**
     * 验证表单数据并返回错误信息
     *
     * @param array $rules 验证规则
     * @param array $data 要验证的数据（默认使用组件公开属性）
     * @return bool 是否通过验证
     */
    public function validateForm(array $rules = [], array $data = []): bool
    {
        if (empty($data)) {
            $data = $this->getPublicProperties();
        }

        $errors = $this->validate($rules);
        if (!empty($errors)) {
            $this->operation('validation:errors', ['errors' => $errors]);
            return false;
        }

        $this->operation('validation:clear');
        return true;
    }

    /**
     * 获取表单错误信息
     *
     * @param string $field 字段名
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->validationErrors[$field] ?? null;
    }

    /**
     * 设置表单错误信息
     *
     * @param string $field 字段名
     * @param string $message 错误消息
     */
    public function setError(string $field, string $message): void
    {
        $this->validationErrors[$field] = $message;
        $this->operation('validation:errors', ['errors' => $this->validationErrors]);
    }

    /**
     * 清除指定字段的错误
     */
    public function clearError(string $field): void
    {
        unset($this->validationErrors[$field]);
    }

    /**
     * 清除所有表单错误
     */
    public function clearErrors(): void
    {
        $this->validationErrors = [];
        $this->operation('validation:clear');
    }

    /**
     * 触发组件级刷新（通知外部组件更新）
     */
    public function notify(string $componentId, string $event, mixed $data = null): void
    {
        LiveNotifier::emit($event, [
            'targetComponent' => $componentId,
            'data' => $data,
        ]);
    }

    public function refresh(string ...$names): void
    {
        foreach ($names as $name) {
            $this->refreshFragments[$name] = 'replace';
        }
    }

    public function append(string $name): void
    {
        $this->refreshFragments[$name] = 'append';
    }

    public function prepend(string $name): void
    {
        $this->refreshFragments[$name] = 'prepend';
    }

    public function getRefreshFragments(): array
    {
        return $this->refreshFragments;
    }

    private function signPayload(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $this->liveSigningKey());
    }

    protected function liveSigningKey(): string
    {
        $appKey = (string) config('app.key', 'secret-fallback');

        if (\Framework\Foundation\AppEnvironment::isWasm()) {
            return hash('sha256', $appKey);
        }

        $session = new \Framework\Http\Session();
        $sessionToken = $session->token();

        return hash_hmac('sha256', $sessionToken, $appKey);
    }
}
