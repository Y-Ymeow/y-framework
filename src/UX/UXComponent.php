<?php

declare(strict_types=1);

namespace Framework\UX;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

abstract class UXComponent
{
    protected string $id;
    protected array $attrs = [];
    protected array $classes = [];
    protected array $children = [];
    protected ?string $liveAction = null;
    protected ?string $liveEvent = null;
    protected ?string $style = null;
    protected array $dataAttrs = [];
    protected array $eventListeners = [];
    protected static array $idCounter = [];

    public function __construct()
    {
        $shortClass = (new \ReflectionClass($this))->getShortName();
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
        if (!isset(self::$idCounter[$key])) self::$idCounter[$key] = 0;
        self::$idCounter[$key]++;
        $this->id = $key . '-' . self::$idCounter[$key];

        AssetRegistry::getInstance()->ui();
        AssetRegistry::getInstance()->ux();
    }

    public static function make(): static
    {
        return new static();
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function class(string $class): static
    {
        $this->classes[] = $class;
        return $this;
    }

    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    public function attr(string $name, string $value): static
    {
        $this->attrs[$name] = $value;
        return $this;
    }

    public function model(string $name): static
    {
        $this->attrs['data-model'] = $name;
        return $this;
    }

    public function data(string $key, string $value): static
    {
        $this->dataAttrs[$key] = $value;
        return $this;
    }

    public function child(mixed $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    public function children(mixed ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    public function liveAction(string $action, string $event = 'click'): static
    {
        $this->liveAction = $action;
        $this->liveEvent = $event;
        return $this;
    }

    public function on(string $event, string $handler): static
    {
        $this->eventListeners[$event] = $handler;
        return $this;
    }

    public function onOpen(string $handler): static
    {
        return $this->on('open', $handler);
    }

    public function onClose(string $handler): static
    {
        return $this->on('close', $handler);
    }

    public function dispatch(string $event, ?string $detail = null): static
    {
        $js = $detail
            ? "\$dispatch('{$event}', {$detail})"
            : "\$dispatch('{$event}')";
        return $this->on('click', $js);
    }

    /**
     * 将 UX 组件转换为 View Element
     * 子类应该重写此方法来定义组件的 DOM 结构
     */
    abstract protected function toElement(): Element;

    /**
     * 获取组件的根标签名（默认 div）
     */
    protected function rootTag(): string
    {
        return 'div';
    }

    /**
     * 构建 Element 的通用属性
     */
    protected function buildElement(Element $el): Element
    {
        $el->id($this->id);

        if ($this->style) {
            $el->style($this->style);
        }

        foreach ($this->classes as $class) {
            $el->class($class);
        }

        foreach ($this->attrs as $name => $value) {
            $el->attr($name, $value);
        }

        foreach ($this->dataAttrs as $key => $value) {
            $el->data($key, $value);
        }

        foreach ($this->eventListeners as $event => $handler) {
            $el->bindOn($event, $handler);
        }

        if ($this->liveAction) {
            $el->liveAction($this->liveAction, $this->liveEvent ?? 'click');
        }

        return $el;
    }

    /**
     * 解析子元素为 Element 可接受的形式
     */
    protected function resolveChild(mixed $child): mixed
    {
        if (is_string($child)) {
            return $child;
        }
        if ($child instanceof self) {
            return $child->toElement();
        }
        if ($child instanceof Element) {
            return $child;
        }
        if (is_object($child) && method_exists($child, 'toElement')) {
            return $child->toElement();
        }
        if (is_object($child) && method_exists($child, 'render')) {
            return $child->render();
        }
        if (is_object($child) && method_exists($child, '__toString')) {
            return (string) $child;
        }
        if (is_array($child)) {
            return array_map([$this, 'resolveChild'], $child);
        }
        return (string) $child;
    }

    /**
     * 将所有 children 添加到 Element
     */
    protected function appendChildren(Element $el): Element
    {
        foreach ($this->children as $child) {
            $el->child($this->resolveChild($child));
        }
        return $el;
    }

    /**
     * 渲染为 HTML 字符串
     */
    public function render(): string
    {
        return $this->toElement()->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
