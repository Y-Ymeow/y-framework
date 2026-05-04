<?php

declare(strict_types=1);

namespace Framework\UX;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

/**
 * UX 组件基类
 *
 * 所有 UX 组件的抽象基类，提供链式 API、Element 桥接、Live 集成等核心能力。
 * 子类只需实现 toElement() 方法定义 DOM 结构。
 *
 * ## 架构设计
 *
 * UX 组件采用 **PHP 定义结构 + JS 提供交互** 的分离架构：
 *
 * - **PHP 端（本类）**: 声明式定义组件的 HTML 结构、属性、样式、数据绑定
 * - **JS 端（y-ux/components/*.js）**: 自动初始化，处理用户交互（点击、输入、拖拽等）
 * - **桥接层（uxLiveBridge.js）**: 双向同步组件值到 LiveComponent，无需手写 LiveAction
 *
 * ## 工作流程
 *
 * 1. 开发者通过链式 API 构建组件：`Modal::make()->title('确认')->content('...')`
 * 2. PHP 渲染为带 `data-*` 属性的 HTML 元素
 * 3. 页面加载后，对应 JS 模块自动扫描 `.ux-xxx` 类名的元素并初始化交互
 * 4. 用户操作触发 JS 事件 → 通过 ux:change 或 data-live-model 同步回 PHP
 *
 * ## JS 交互能力（自动关联）
 *
 * 每个组件自动关联同名 JS 模块：
 * - Modal 组件 → `src/statics/y-ux/components/modal.js`（提供 open/close/init 方法）
 * - DatePicker 组件 → `datePicker.js`（提供日期选择、范围选择）
 * - Calendar 组件 → `calendar.js`（提供月份切换、日期选中）
 * - Transfer 组件 → `transfer.js`（提供穿梭框交互）
 * - ... 以此类推
 *
 * @ux-category Core
 * @ux-since 1.0.0
 *
 * @ux-example
 * // 基础用法
 * $btn = Button::make()
 *     ->label('点击')
 *     ->primary()
 *     ->liveAction('save');
 *
 * // Modal 弹窗（JS 自动处理 open/close）
 * $modal = Modal::make()
 *     ->title('确认删除')
 *     ->content('确定要删除吗？')
 *     ->onConfirm('$dispatch("delete")');
 * @ux-example-end
 */
abstract class UXComponent
{
    protected string $id;
    protected array $attrs = [];
    protected array $classes = [];
    protected array $children = [];
    protected ?string $liveAction = null;
    protected ?string $liveEvent = null;
    protected bool $isStream = false;
    protected ?string $uxModel = null;
    protected ?string $style = null;
    protected array $dataAttrs = [];
    protected array $eventListeners = [];
    protected static array $idCounter = [];

    /**
     * 组件名称（用于 JS 注册，如 'modal', 'tabs'）
     * 子类可覆盖，默认为类名小写
     */
    protected static ?string $componentName = null;

    /**
     * 组件是否已初始化（防止重复注册资源）
     */
    private static array $initializedComponents = [];

    public function __construct()
    {
        $shortClass = (new \ReflectionClass($this))->getShortName();
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
        if (!isset(self::$idCounter[$key])) self::$idCounter[$key] = 0;
        self::$idCounter[$key]++;
        $this->id = $key . '-' . self::$idCounter[$key];

        // 按需加载：只注册 UI 核心和 UX 框架（不含所有组件）
        AssetRegistry::getInstance()->ui();
        AssetRegistry::getInstance()->ux();

        // 调用组件自身的初始化逻辑（注册 JS/CSS 片段）
        $this->init();
    }

    /**
     * 组件初始化 - 子类覆盖此方法注册 JS/CSS 片段
     *
     * 示例：
     * protected function init(): void
     * {
     *     $this->registerJs('modal', 'UX.register("modal", { ... })');
     *     $this->registerCss('.ux-modal { ... }');
     * }
     */
    protected function init(): void
    {
        // 子类实现
    }

    /**
     * 注册组件 JS 代码片段到 AssetRegistry
     *
     * @param string $componentName 组件名称（如 'modal', 'tabs'）
     * @param string $jsCode JS 组件实现代码（会被包裹在 UX.register 中）
     */
    protected function registerJs(string $componentName, string $jsCode): void
    {
        $key = static::class . ':' . $componentName;

        if (isset(self::$initializedComponents[$key])) {
            return;
        }
        self::$initializedComponents[$key] = true;

        $wrappedJs = "UX.register('{$componentName}', (function() {\n{$jsCode}\n})());";

        AssetRegistry::getInstance()->registerScript('ux:' . $componentName, $wrappedJs);
    }

    /**
     * 注册内联 CSS 样式到 AssetRegistry
     *
     * @param string $cssCode CSS 代码
     */
    protected function registerCss(string $cssCode): void
    {
        $key = static::class . ':css';
        if (isset(self::$initializedComponents[$key])) {
            return;
        }
        self::$initializedComponents[$key] = true;

        AssetRegistry::getInstance()->inlineStyle($cssCode);
    }

    /**
     * 获取组件名称
     */
    protected function getComponentName(): string
    {
        if (static::$componentName !== null) {
            return static::$componentName;
        }

        $shortClass = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
    }

    /**
     * 静态工厂方法，创建组件实例
     * @return static
     * @ux-example Button::make()
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * 设置组件 ID
     * @param string $id 唯一标识
     * @return static
     * @ux-example Button::make()->id('submit-btn')
     */
    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 添加 CSS 类名
     * @param string $class 类名
     * @return static
     * @ux-example Button::make()->class('my-btn')
     */
    public function class(string $class): static
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * 设置内联样式
     * @param string $style CSS 样式字符串
     * @return static
     * @ux-example Card::make()->style('border: 1px solid red;')
     */
    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * 设置 HTML 属性
     * @param string $name 属性名
     * @param string $value 属性值
     * @return static
     * @ux-example Input::make()->attr('autocomplete', 'off')
     */
    public function attr(string $name, string $value): static
    {
        $this->attrs[$name] = $value;
        return $this;
    }

    /**
     * 设置 data-model 绑定
     * @param string $name 绑定属性名
     * @return static
     * @ux-example Input::make()->model('username')
     */
    public function model(string $name): static
    {
        $this->attrs['data-model'] = $name;
        return $this;
    }

    /**
     * 设置 Live 双向绑定，通过桥接层自动同步 UX 组件值到 LiveComponent 属性
     *
     * ## 工作原理
     * 1. PHP 在组件内生成隐藏 input：`<input data-live-model="propertyName">`
     * 2. JS 侧（uxLiveBridge.js）监听组件的 `ux:change` 事件
     * 3. 用户操作组件时，JS 更新隐藏 input 的值 → 触发 Live 同步
     * 4. LiveComponent 的 `$propertyName` 自动更新，无需手写 Action
     *
     * @param string $property LiveComponent 公开属性名
     * @return static
     *
     * @ux-since 1.0.0
     * @ux-example
     * // DatePicker 双向绑定
     * DatePicker::make()->liveModel('eventDate')
     * // 用户选择日期后 $this->eventDate 自动更新为 "2026-05-02"
     *
     * // Rate 评分绑定
     * Rate::make()->liveModel('rating')
     * // 用户点击星级后 $this->rating 自动更新为 5
     *
     * // Transfer 穿梭框绑定（JSON 格式）
     * Transfer::make()->liveModel('selectedIds')
     * // $this->selectedIds = [1, 3, 5]
     * @ux-example-end
     */
    public function liveModel(string $property): static
    {
        $this->uxModel = $property;
        return $this;
    }

    /**
     * 设置 data-* 属性
     * @param string $key 属性键（不含 data- 前缀）
     * @param string $value 属性值
     * @return static
     * @ux-example Button::make()->data('toggle', 'modal')
     */
    public function data(string $key, string $value): static
    {
        $this->dataAttrs[$key] = $value;
        return $this;
    }

    /**
     * 订阅 SSE 频道（data-live-sse）
     *
     * 组件将自动订阅指定的 SSE 频道，接收服务器推送的消息。
     * 当收到 `live:action` 事件时，会自动调用对应的 LiveAction。
     *
     * @param string ...$channels 频道名称列表
     * @return static
     *
     * @ux-since 2.0
     * @ux-example
     * // 订阅单个频道
     * Container::make()->id('dashboard')->dataLiveSse('dashboard')
     *
     * // 订阅多个频道
     * Container::make()
     *     ->id('notifications')
     *     ->dataLiveSse('notifications', 'orders', 'system')
     *
     * // 后端推送更新
     * SseHub::liveAction('dashboard', 'refreshData');
     * // 前端自动调用 dashboard 组件的 refreshData() 方法
     */
    public function dataLiveSse(string ...$channels): static
    {
        $this->dataAttrs['live-sse'] = implode(',', $channels);
        return $this;
    }

    /**
     * 添加子元素
     * @param mixed $child 支持 Element、UXComponent、字符串
     * @return static
     * @ux-example Card::make()->child(Element::make('p')->text('内容'))
     */
    public function child(mixed $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * 批量添加子元素
     * @param mixed ...$children
     * @return static
     * @ux-example Row::make()->children($btn1, $btn2, $btn3)
     */
    public function children(mixed ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    /**
     * 设置 Live Action，点击时触发后端方法
     * @param string $action Action 方法名
     * @param string $event 触发事件类型
     * @return static
     * @ux-example Button::make()->liveAction('save')
     * @ux-default event='click'
     */
    public function liveAction(string $action, string $event = 'click'): static
    {
        $this->liveAction = $action;
        $this->liveEvent = $event;
        return $this;
    }

    /**
     * 标记此动作为流式响应
     *
     * 添加 data-stream 属性，前端会将请求发送到 /live/stream 端点
     * 而非 /live/update，以支持 NDJSON 流式输出。
     *
     * @return static
     */
    public function stream(): static
    {
        $this->isStream = true;
        return $this;
    }

    /**
     * 绑定事件监听器
     *
     * 将 JS 事件处理器绑定到组件根元素上，使用 data-on:event 属性。
     *
     * ## 可用的事件类型
     * - **组件内置事件**: open, close, change, confirm, cancel（由各 JS 模块触发）
     * - **DOM 原生事件**: click, input, focus, blur, keydown 等
     * - **自定义事件**: 任何通过 new CustomEvent() 派发的事件
     *
     * ## JS 表达式语法
     * 支持 LiveComponent 的客户端表达式：
     * - `$dispatch('event')` — 派发 Livewire/Live 事件
     * - `$set('property', value)` — 设置属性值
     * - `$call('method', args)` — 调用方法
     * - `console.log(...)` — 调试输出
     *
     * @param string $event 事件名
     * @param string $handler JS 处理代码
     * @return static
     *
     * @ux-since 1.0.0
     * @ux-example
     * // 点击按钮派发事件
     * Button::make()->on('click', '$dispatch("item-deleted", { id: 123 })')
     *
     * // Modal 关闭时刷新数据
     * Modal::make()->on('close', '$dispatch("refresh")')
     *
     * // DatePicker 日期变更时执行逻辑
     * DatePicker::make()->on('change', 'console.log(event.detail.value)')
     * @ux-example-end
     */
    public function on(string $event, string $handler): static
    {
        $this->eventListeners[$event] = $handler;
        return $this;
    }

    /**
     * 绑定 open 事件
     * @param string $handler JS 处理代码
     * @return static
     * @ux-example Modal::make()->onOpen('console.log("opened")')
     */
    public function onOpen(string $handler): static
    {
        return $this->on('open', $handler);
    }

    /**
     * 绑定 close 事件
     * @param string $handler JS 处理代码
     * @return static
     * @ux-example Modal::make()->onClose('console.log("closed")')
     */
    public function onClose(string $handler): static
    {
        return $this->on('close', $handler);
    }

    /**
     * 派发自定义事件，绑定到 click
     *
     * 便捷方法，等价于 `->on('click', '$dispatch(...)')`。
     * 点击组件时自动派发指定事件到 LiveComponent 或父组件。
     *
     * ## 典型用途
     * - 操作按钮：删除、保存、确认等操作的事件触发
     * - 列表项：选中、展开、详情查看
     * - 表格行：编辑、跳转
     *
     * @param string $event 事件名（如 'delete', 'save', 'select'）
     * @param string|null $detail 事件数据（JSON 对象或变量名）
     * @return static
     *
     * @ux-since 1.0.0
     * @ux-example
     * // 删除按钮 — 无参数
     * Button::make()->label('删除')->danger()->dispatch('delete')
     *
     * // 带数据的派发
     * Button::make()->label('编辑')->dispatch('edit', '{ id: item.id }')
     *
     * // 在表格中使用
     * foreach ($items as $item) {
     *     Button::make()
     *         ->label('查看')
     *         ->dispatch('view', "{ id: {$item['id']} }");
     * }
     * @ux-example-end
     */
    public function dispatch(string $event, ?string $detail = null): static
    {
        $js = $detail
            ? "\$dispatch('{$event}', {$detail})"
            : "\$dispatch('{$event}')";
        return $this->on('click', $js);
    }

    /**
     * 将 UX 组件转换为 View Element（子类必须实现）
     * @ux-internal
     */
    abstract protected function toElement(): Element;

    /**
     * 获取组件的根标签名
     * @ux-internal
     */
    protected function rootTag(): string
    {
        return 'div';
    }

    /**
     * 构建 Element 的通用属性
     * @ux-internal
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

        if ($this->isStream) {
            $el->attr('data-stream', '');
        }

        if ($this->uxModel) {
            $el->data('ux-model', $this->uxModel);
        }

        return $el;
    }

    /**
     * 解析子元素为 Element 可接受的形式
     * @ux-internal
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
     * @ux-internal
     */
    protected function appendChildren(Element $el): Element
    {
        foreach ($this->children as $child) {
            $el->child($this->resolveChild($child));
        }
        return $el;
    }

    /**
     * 创建 Live 桥接隐藏 input，自动设置 data-live-model
     * @param string $value 初始值
     * @ux-internal
     */
    protected function createLiveModelInput(string $value = ''): ?Element
    {
        if (!$this->uxModel) {
            return null;
        }

        return Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->uxModel)
            ->attr('value', $value)
            ->attr('data-live-model', $this->uxModel)
            ->attr('data-live-debounce', '0')
            ->class('ux-live-bridge-input');
    }

    /**
     * 渲染为 HTML 字符串
     * @ux-internal
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
