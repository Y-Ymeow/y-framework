<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Concerns\HasActions;
use Framework\Component\Live\Concerns\HasOperations;
use Framework\Component\Live\Concerns\HasProperties;
use Framework\Component\Live\Concerns\HasState;
use Framework\View\Base\Element;
use Framework\View\FragmentRegistry;

abstract class LiveComponent
{
    use HasActions;
    use HasOperations;
    use HasProperties;
    use HasState;

    protected string $componentId;
    protected static array $idCounter = [];
    private array $refreshFragments = [];
    private array $manualUpdates = [];
    private array $validationErrors = [];
    protected bool $mountCalled = false;
    private array $computedCache = [];

    public function __construct()
    {
        $shortClass = (new \ReflectionClass($this))->getShortName();
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
        if (!isset(self::$idCounter[$key])) self::$idCounter[$key] = 0;
        self::$idCounter[$key]++;
        $this->componentId = $key . '-' . self::$idCounter[$key];
    }

    public static function make(array $props = [], array $routeParams = []): static
    {
        $instance = new static();
        $instance->_invoke($routeParams);
        $instance->propValues = $props;
        return $instance;
    }

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

    public function mount(): void {}

    public function toHtml(): string
    {
        $state = $this->serializeState();
        $publicProps = $this->getPublicProperties();

        $stateAttr = htmlspecialchars(json_encode($publicProps, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

        $listenersAttr = '';
        $listenerEvents = $this->getListenerEvents();
        if (!empty($listenerEvents)) {
            $listenersAttr = sprintf(' data-live-listeners="%s"', htmlspecialchars(implode(',', $listenerEvents), ENT_QUOTES, 'UTF-8'));
        }

        return sprintf(
            '<div data-live="%s" data-live-id="%s" data-state="%s" data-live-state="%s"%s>%s</div>',
            static::class,
            $this->componentId,
            $stateAttr,
            $state,
            $listenersAttr,
            $this->render()
        );
    }

    public function mountHook(): void {}

    public function hydrate(): void {}

    public function dehydrate(): void
    {
        $this->persistProperties();
    }

    public function render(): Element
    {
        return new Element('div');
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    public function getListenerEvents(): array
    {
        $events = [];
        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(\Framework\Component\Live\Attribute\LiveListener::class);
            foreach ($attrs as $attr) {
                $listener = $attr->newInstance();
                $events[] = $listener->event;
            }
        }
        return array_unique($events);
    }

    /**
     * 刷新指定 Fragment（支持局部刷新模式）
     */
    public function refresh(string $name, string $mode = 'replace'): void
    {
        $this->refreshFragments[$name] = $mode;
    }

    public function getRefreshFragments(): array
    {
        return $this->refreshFragments;
    }

    public function getFragments(): array
    {
        return FragmentRegistry::getInstance()->getFragments();
    }

    public function getFragment(string $name): ?array
    {
        return FragmentRegistry::getInstance()->getFragment($name);
    }

    /**
     * 校验公开属性
     */
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