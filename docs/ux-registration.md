# UX 注册机制与组件体系文档

> UX 模块的组件注册、Form Builder 注册、Live/普通组件区分机制。

---

## 目录

1. [架构概览](#1-架构概览)
2. [UX 组件注册机制](#2-ux-组件注册机制)
3. [Form Builder 注册机制](#3-form-builder-注册机制)
4. [Live Field vs 普通 Field](#4-live-field-vs-普通-field)
5. [Live UX 组件 vs 普通 UX 组件](#5-live-ux-组件-vs-普通-ux-组件)
6. [前端注册机制](#6-前端注册机制)
7. [完整对照表](#7-完整对照表)

---

## 1. 架构概览

### 1.1 组件继承体系

```
AbstractLiveComponent
└── EmbeddedLiveComponent          ← 可嵌入父组件的 Live 组件
    └── UXLiveComponent            ← UX + Live 双重身份
        └── BaseField              ← 表单字段基类（Components/ 目录）
            ├── TextInput
            ├── Textarea
            ├── Select
            ├── Checkbox
            ├── RadioGroup
            ├── LiveTextInput       ← 带 #[State] 的 Live Field
            ├── MediaPicker         ← 带 #[State] + #[LiveAction] 的 Live Field
            └── LinkSelector        ← 带 #[State] + #[LiveAction] 的 Live Field

UXComponent                        ← 纯 UX 组件（无 Live 能力）
├── FormField                      ← 旧版表单字段（Form/ 根目录）
│   ├── Input
│   ├── Select
│   ├── Textarea
│   ├── RichEditor
│   └── ...（Checkbox, RadioGroup, DatePicker 等）
├── FormBuilder                    ← 表单构建器
├── Button
├── Modal
├── DataTable
└── ...（所有非 Live 的 UX 组件）

LiveComponent                      ← 顶层 Live 组件
└── LiveRichEditor                 ← 独立 Live 表单组件
```

### 1.2 两套表单字段体系

```
Form/Components/ 目录（新）         Form/ 根目录（旧）
─────────────────────────          ────────────────────
BaseField → UXLiveComponent        FormField → UXComponent
  ├── TextInput                      ├── Input
  ├── Textarea                       ├── Textarea
  ├── Select                         ├── Select
  ├── Checkbox                       ├── Checkbox
  ├── RadioGroup                     ├── RadioGroup
  ├── LiveTextInput                  ├── RichEditor
  ├── MediaPicker                    ├── DatePicker
  └── LinkSelector                   ├── FileUpload
                                      └── ...
```

**关键区别**：`Components/` 目录下的字段继承 `UXLiveComponent`，可以拥有 `#[State]` 和 `#[LiveAction]`；`Form/` 根目录的字段继承 `UXComponent`，只能通过 `data-model` / `data-live-model` 做前端绑定。

---

## 2. UX 组件注册机制

### 2.1 PHP 端注册流程

每个 UX 组件在构造时自动完成注册：

```php
abstract class UXComponent
{
    public function __construct()
    {
        // 1. 生成唯一 ID
        $shortClass = (new \ReflectionClass($this))->getShortName();
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
        $this->id = $key . '-' . (++self::$idCounter[$key]);

        // 2. 按需加载核心资源
        AssetRegistry::getInstance()->ui();   // y-directive + y-live
        AssetRegistry::getInstance()->ux();   // y-ux 框架

        // 3. 调用组件自身的初始化逻辑
        $this->init();
    }
}
```

### 2.2 init() 方法 — JS/CSS 注册

子类覆盖 `init()` 注册组件的 JS 和 CSS：

```php
class Modal extends UXComponent
{
    protected static ?string $componentName = 'modal';

    protected function init(): void
    {
        // 注册 JS 组件到 UX.register()
        $this->registerJs('modal', '
            const Modal = {
                open(id) { ... },
                close(id) { ... },
                init() { ... },
                liveHandler(op) { ... }   // ← Live 操作处理器
            };
            return Modal;
        ');

        // 注册 CSS 样式
        $this->registerCss('
            .ux-modal { ... }
            .ux-modal-open { ... }
        ');
    }
}
```

### 2.3 registerJs() 去重机制

```php
protected function registerJs(string $componentName, string $jsCode): void
{
    $key = static::class . ':' . $componentName;

    // 同一个类 + 同一个组件名只注册一次
    if (isset(self::$initializedComponents[$key])) {
        return;
    }
    self::$initializedComponents[$key] = true;

    // 包裹为 UX.register() 调用
    $wrappedJs = "UX.register('{$componentName}', (function() {\n{$jsCode}\n})());";
    AssetRegistry::getInstance()->registerScript('ux:' . $componentName, $wrappedJs);
}
```

**要点**：
- 使用 `static::class` 作为 key 的一部分，不同子类可以注册同名组件
- `registerCss()` 同理，使用 `static::class . ':css'` 去重
- JS 代码会被包裹为 `UX.register(name, ...)` 格式

### 2.4 componentName 解析规则

```php
protected function getComponentName(): string
{
    // 1. 优先使用静态属性
    if (static::$componentName !== null) {
        return static::$componentName;
    }

    // 2. 默认从类名转换：Modal → modal, DatePicker → date-picker
    $shortClass = (new \ReflectionClass($this))->getShortName();
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $shortClass));
}
```

### 2.5 UXLiveComponent 的注册

```php
abstract class UXLiveComponent extends EmbeddedLiveComponent
{
    protected bool $isUxComponent = true;
    protected bool $autoRefreshOnParentUpdate = false;
    protected ?string $uxModel = null;

    public function __construct()
    {
        parent::__construct();  // EmbeddedLiveComponent 构造

        // 同样加载 UX 资源
        AssetRegistry::getInstance()->ui();
        AssetRegistry::getInstance()->ux();

        $this->boot();  // 子类覆盖的初始化钩子
    }

    // liveModel 绑定
    public function liveModel(string $property): static
    {
        $this->uxModel = $property;
        return $this;
    }

    // render() 自动添加 data-ux-model
    public function render(): Element
    {
        $el = $this->toElement();
        if ($this->uxModel) {
            $el->data('ux-model', $this->uxModel);
        }
        return $el;
    }
}
```

**UXLiveComponent vs UXComponent 注册差异**：

| 特性 | UXComponent | UXLiveComponent |
|------|-------------|-----------------|
| 继承链 | 独立基类 | EmbeddedLiveComponent → AbstractLiveComponent |
| JS 注册 | `registerJs()` + `UX.register()` | 同左（通过 boot()） |
| CSS 注册 | `registerCss()` | 同左 |
| Live 状态 | 无 `#[State]` | 支持 `#[State]` |
| Live 动作 | 只能通过 `liveAction()` 绑定 | 支持 `#[LiveAction]` |
| 父子关系 | 无 | `setParent()` 注入 |
| 渲染方式 | `toElement()` → Element | `render()` → Element，外层包 `toHtml()` |

---

## 3. Form Builder 注册机制

### 3.1 FormBuilder 类结构

```php
class FormBuilder extends UXComponent
{
    use HasComponents;

    protected static array $macros = [];
    protected static array $registeredComponents = [];
}
```

### 3.2 两种添加字段的方式

#### 方式一：快捷方法（Builder 方法）

FormBuilder 提供了内置的快捷方法，直接创建并添加字段：

```php
public function text(string $name, string|array $label = '', array $options = []): static
{
    $input = TextInput::make($name);
    if ($label) $input->label($label);
    // ... 应用 options
    return $this->add($this->applyFieldOptions($input, $options));
}

public function select(string $name, string|array $label = '', array $options = [], array $selectOptions = []): static
{
    $select = Select::make($name);
    // ...
    return $this->add($this->applyFieldOptions($select, $options));
}
```

**已注册的快捷方法**：

| 方法 | 创建的组件 | 说明 |
|------|-----------|------|
| `text()` | TextInput | 文本输入 |
| `email()` | TextInput (email) | 邮箱输入 |
| `password()` | TextInput (password) | 密码输入 |
| `number()` | TextInput (number) | 数字输入 |
| `textarea()` | Textarea | 多行文本 |
| `select()` | Select | 下拉选择 |
| `checkbox()` | Checkbox | 复选框 |
| `radio()` | RadioGroup | 单选组 |
| `hidden()` | TextInput (hidden) | 隐藏字段 |
| `file()` | TextInput (file) | 文件上传 |
| `richEditor()` | RichEditor | 富文本编辑器 |
| `blockEditor()` | BlockEditor | 块编辑器 |

#### 方式二：add() 方法（手动添加）

```php
public function add(FormComponent|UXComponent|Element $component): static
{
    if ($this->submitMode && method_exists($component, 'submitMode')) {
        $component->submitMode(true);
    }
    $this->components[] = $component;
    return $this;
}

// 批量添加
public function fields(array $components): static
{
    foreach ($components as $component) {
        if ($component instanceof FormComponent || $component instanceof UXComponent || $component instanceof Element) {
            $this->add($component);
        }
    }
    return $this;
}
```

### 3.3 自定义组件注册

#### registerComponent() — 别名注册

```php
public static function registerComponent(string $alias, string $class): void
{
    static::$registeredComponents[$alias] = $class;
}
```

将一个类注册为别名，之后可以通过别名引用。此机制预留但尚未在快捷方法中使用。

#### macro() — 宏方法注册

```php
public static function macro(string $name, callable $callback): void
{
    static::$macros[$name] = $callback;
}

public function __call(string $name, array $arguments)
{
    if (isset(static::$macros[$name])) {
        return \Closure::fromCallable(static::$macros[$name])->call($this, ...$arguments);
    }
    throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
}
```

**使用示例**：

```php
FormBuilder::macro('mediaPicker', function (string $name, string $label = '') {
    return $this->add(
        MediaPicker::make($name)->label($label)
    );
});

// 之后在 FormBuilder 中使用
$form->mediaPicker('avatar', '头像');
```

### 3.4 applyFieldOptions() — 通用选项应用

所有快捷方法创建的字段都会经过 `applyFieldOptions()`：

```php
protected function applyFieldOptions(object $field, array $options): object
{
    // 字符串选项：placeholder, help, default, value
    foreach (['placeholder', 'help', 'default', 'value'] as $method) {
        if (array_key_exists($method, $options) && method_exists($field, $method)) {
            $field->{$method}($options[$method]);
        }
    }

    // 布尔选项：required, disabled, readonly
    foreach (['required', 'disabled', 'readonly'] as $method) {
        if (!empty($options[$method]) && method_exists($field, $method)) {
            $field->{$method}();
        }
    }

    // CSS 类
    if (!empty($options['class']) && method_exists($field, 'class')) {
        foreach ((array) $options['class'] as $class) {
            $field->class((string) $class);
        }
    }

    // submitMode 传播
    if ($this->submitMode && method_exists($field, 'submitMode')) {
        $field->submitMode(true);
    }

    return $field;
}
```

### 3.5 HasComponents — 组件渲染与 Live 检测

```php
trait HasComponents
{
    protected array $components = [];

    protected function renderComponents(): array
    {
        $elements = [];
        foreach ($this->components as $component) {
            // 关键分支：检测是否为 Live 组件
            if (EmbeddedLiveComponent::isLiveComponent($component)) {
                // Live Field：设置父组件、调用 _invoke()、用 toHtml() 渲染
                if ($this instanceof EmbeddedLiveComponent) {
                    $component->setParent($this);
                }
                $component->_invoke();
                $elements[] = Element::make('div')->html($component->toHtml());
            } elseif (method_exists($component, 'render')) {
                // 普通 Field：直接调用 render()
                $elements[] = $component->render();
            }
        }
        return $elements;
    }
}
```

**渲染流程对比**：

```
普通 Field (TextInput, Select, ...):
  FormBuilder.renderComponents()
    → $component->render()           // 返回 Element
    → Element 直接插入 DOM

Live Field (MediaPicker, LinkSelector, LiveTextInput):
  FormBuilder.renderComponents()
    → isLiveComponent() === true     // 检测到 #[State] 或 #[LiveAction]
    → setParent($this)               // 注入父组件
    → _invoke()                      // 触发 mount()
    → toHtml()                       // 渲染为带 data-live 属性的 HTML
    → Element::make('div')->html()   // 包装为 Element
```

### 3.6 isLiveComponent() 检测逻辑

```php
public static function isLiveComponent(object $component): bool
{
    // 1. 必须是 EmbeddedLiveComponent 实例
    if (!($component instanceof EmbeddedLiveComponent)) {
        return false;
    }

    $ref = new \ReflectionClass($component);

    // 2. 检查是否有 #[State] 属性
    foreach ($ref->getProperties() as $prop) {
        if (!empty($prop->getAttributes(Attribute\State::class))) {
            return true;
        }
    }

    // 3. 检查是否有 #[LiveAction] 方法
    foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
        if (!empty($method->getAttributes(Attribute\LiveAction::class))) {
            return true;
        }
    }

    return false;
}
```

**判定规则**：只要是 `EmbeddedLiveComponent` 的子类，且拥有至少一个 `#[State]` 属性或 `#[LiveAction]` 方法，就被识别为 Live 组件。

### 3.7 Form Layout 组件

布局组件实现 `FormLayout` 接口（继承自 `FormComponent`），使用 `HasComponents` trait：

```php
interface FormLayout extends FormComponent
{
    public function schema(array|callable $components): static;
    public function getComponents(): array;
    public function hasComponents(): bool;
}
```

| 布局组件 | 用途 | 特点 |
|---------|------|------|
| Section | 分组 | 标题 + 描述 + 可折叠 |
| Grid | 网格布局 | 列数 + 间距 |
| Tabs | 标签页 | 多 Tab 切换 |
| Tab | 单个标签页 | Tabs 的子组件 |

布局组件同样通过 `HasComponents::renderComponents()` 渲染子组件，因此也支持 Live Field 的自动检测和渲染。

---

## 4. Live Field vs 普通 Field

### 4.1 继承链差异

```
普通 Field:
  UXComponent → FormField → Input / Select / Textarea / ...

Live Field:
  AbstractLiveComponent → EmbeddedLiveComponent → UXLiveComponent → BaseField → TextInput / MediaPicker / ...
```

### 4.2 核心能力对比

| 能力 | 普通 Field (FormField) | Live Field (BaseField) |
|------|----------------------|----------------------|
| 表单字段接口 | `FormField` 接口 | `FormField` 接口（同样实现） |
| 链式 API | ✅ | ✅ |
| `data-model` 绑定 | ✅ 前端本地状态 | ✅ |
| `data-live-model` 绑定 | ✅ 通过 `liveModel()` | ✅ 通过 `liveModel()` |
| `#[State]` 属性 | ❌ | ✅ |
| `#[LiveAction]` 方法 | ❌ | ✅ |
| `#[LiveListener]` 监听 | ❌ | ✅ |
| 父子组件注入 | ❌ | ✅ `setParent()` |
| `mount()` / `hydrate()` | ❌ | ✅ |
| `emit()` 事件 | ❌ | ✅ |
| `$this->toast()` | ❌ | ✅ |
| `$this->openModal()` | ❌ | ✅ |
| Fragment 分片更新 | ❌ | ✅ `liveFragment()` |
| 独立 Action 处理 | ❌ 需父组件实现 | ✅ 自包含 |
| 服务端状态持久化 | ❌ | ✅ |
| 渲染输出 | `render()` → Element | `render()` → Element，外层 `toHtml()` 包裹 |

### 4.3 数据绑定方式对比

#### 普通 Field — data-model

```php
// TextInput (普通 Field)
$input = TextInput::make('username');
// 渲染结果：<input data-model="username" ...>
// 前端行为：y-directive 本地状态管理，不发送请求
```

#### 普通 Field — submitMode

```php
// TextInput (submitMode)
$input = TextInput::make('username')->submitMode(true);
// 渲染结果：<input data-submit-field="username" ...>
// 前端行为：表单提交时收集字段值
```

#### Live Field — data-model

```php
// TextInput (Live Field，无 #[State])
$input = TextInput::make('username');
// 渲染结果：<input data-model="username" ...>
// 同普通 Field，前端本地状态
```

#### Live Field — data-live-model

```php
// LiveTextInput (Live Field，有 #[State])
$input = LiveTextInput::make('username')->live();
// 渲染结果：<input data-live-model.live="inputValue" ...>
// 前端行为：每次输入都发送 /live/state 请求同步到服务端
```

#### Live Field — 自包含 Action

```php
// MediaPicker (Live Field，有 #[State] + #[LiveAction])
$picker = MediaPicker::make('avatar');
// 渲染结果：整个字段被 data-live 包裹
// <div data-live="MediaPicker" data-live-id="..." data-live-state="...">
//   ... 字段内容 ...
//   <button data-action="click->selectMedia">选择图片</button>
// </div>
// 前端行为：点击按钮 → /live/action → MediaPicker::selectMedia()
// 无需父组件实现任何 Action
```

### 4.4 何时选择 Live Field

**需要 Live Field 的场景**：
- 字段有复杂交互（如 MediaPicker 需要打开弹窗选择图片）
- 字段需要服务端计算（如搜索建议、联动选择）
- 字段需要实时同步到服务端（如自动保存的文本输入）
- 字段需要独立管理状态（如 LinkSelector 的 url/target/label）

**普通 Field 足够的场景**：
- 简单的文本输入、下拉选择
- 表单整体提交（submitMode）
- 不需要服务端交互的纯前端字段

### 4.5 普通 Field 转 Live Field 的方法

给 BaseField 子类添加 `#[State]` 或 `#[LiveAction]` 即可自动升级：

```php
// 之前：普通 TextInput
class TextInput extends BaseField
{
    // 无 #[State]，无 #[LiveAction]
    // → isLiveComponent() 返回 false
    // → 渲染时走 render() 分支
}

// 之后：Live TextInput
class LiveTextInput extends BaseField
{
    #[State(frontendEditable: true)]
    public string $inputValue = '';

    // 有 #[State]
    // → isLiveComponent() 返回 true
    // → 渲染时走 toHtml() 分支，自动包裹 data-live
}
```

---

## 5. Live UX 组件 vs 普通 UX 组件

### 5.1 分类总览

#### 普通 UX 组件（继承 UXComponent）

这些组件只定义结构和样式，交互由前端 JS 处理：

| 类别 | 组件 |
|------|------|
| UI | Button, Navigate, Accordion |
| Dialog | Modal, Drawer, Toast, ConfirmDialog |
| Display | Card, Badge, Avatar, Tag, Collapse, Divider, StatCard, Timeline, ListView, QRCode, Watermark |
| Feedback | Alert, EmptyState, LoadingOverlay, Progress, Skeleton |
| Data | DataTable, DataGrid, DataList, DataCard, DataTree, Calendar, DescriptionList, BatchActionsMenu |
| Navigation | Breadcrumb, Pagination, Tabs, Steps, LanguageSwitcher |
| Overlay | Popover, Tooltip |
| Media | Image, Carousel |
| Menu | Menu, Dropdown |
| Layout | Layout, Grid, Row |
| Chart | Chart |
| Form (旧) | Input, Select, Textarea, Checkbox, RadioGroup, RichEditor, DatePicker, FileUpload, ImageUpload, SwitchField, Slider, Rate, ColorPicker, TagInput, SearchInput, TreeSelect, Transfer, DateRangePicker, BlockEditor |

#### Live UX 组件（继承 UXLiveComponent）

这些组件拥有后端状态和动作能力：

| 组件 | #[State] | #[LiveAction] | 说明 |
|------|----------|---------------|------|
| TextInput | ❌ | ❌ | 基础文本输入（非 Live） |
| Textarea | ❌ | ❌ | 基础多行文本（非 Live） |
| Select | ❌ | ❌ | 基础下拉选择（非 Live） |
| Checkbox | ❌ | ❌ | 基础复选框（非 Live） |
| RadioGroup | ❌ | ❌ | 基础单选组（非 Live） |
| LiveTextInput | ✅ `inputValue` | ❌ | 实时同步文本输入 |
| MediaPicker | ✅ `selectedUrl`, `filterType` | ✅ `selectMedia`, `removeMedia`, `filterMedia` | 图片选择器 |
| LinkSelector | ✅ `linkUrl`, `linkTarget`, `linkLabel` | ✅ `applyLink`, `removeLink` | 链接选择器 |

#### 独立 Live 组件（继承 LiveComponent）

| 组件 | 说明 |
|------|------|
| LiveRichEditor | 独立 Live 富文本编辑器，直接继承 LiveComponent（非 UXLiveComponent） |

### 5.2 渲染方式对比

#### 普通 UX 组件

```php
// PHP
$btn = Button::make()->label('提交')->primary()->liveAction('save');

// 渲染流程
Button::toElement()
  → Element::make('button')
  → buildElement() 添加 class/attr/data-action
  → 返回 Element

// HTML 输出
<button class="ux-btn ux-btn-primary" data-action="click->save">提交</button>
```

#### Live UX 组件

```php
// PHP
$picker = MediaPicker::make('avatar');

// 渲染流程（在 HasComponents::renderComponents() 中）
1. isLiveComponent($picker) === true
2. setParent($this)           // 注入父组件
3. $picker->_invoke()          // 触发 mount()
4. $picker->toHtml()           // 渲染完整 Live HTML

// toHtml() 内部
1. getLiveMetadata()           // 收集 __component, __id, __state, __actions, __listeners
2. json_encode → htmlspecialchars
3. render() → Element         // 组件自身的 DOM 结构
4. 包裹为 <div data-live="..." data-live-id="..." data-live-state="...">

// HTML 输出
<div data-live="Framework\UX\Form\Components\MediaPicker"
     data-live-id="media-picker-abc123"
     data-live-state="{&quot;__component&quot;:...}"
     data-live-parent-id="parent-xyz789">
  <div class="ux-form-group">
    ... 字段内容 ...
    <button data-action="click->selectMedia">选择图片</button>
  </div>
</div>
```

### 5.3 交互方式对比

#### 普通 UX 组件 — 前端驱动

```
用户点击 → JS 事件监听 → data-action 解析 → /live/action → 父组件方法
```

普通 UX 组件的 `liveAction()` 只是在元素上添加 `data-action` 属性，实际调用的是**父级 LiveComponent** 的方法：

```php
// Button 的 liveAction 最终渲染为：
<button data-action="click->save">提交</button>

// "save" 方法必须在父级 LiveComponent 中定义
class MyPage extends LiveComponent
{
    #[LiveAction]
    public function save(array $params): void { ... }
}
```

#### Live UX 组件 — 自包含

```
用户点击 → JS 事件监听 → data-action 解析 → /live/action → 自身方法
```

Live UX 组件的 Action 调用的是**自身**的方法：

```php
// MediaPicker 内的按钮
<button data-action="click->selectMedia">选择图片</button>

// selectMedia 方法定义在 MediaPicker 自身
class MediaPicker extends BaseField
{
    #[LiveAction]
    public function selectMedia(array $params): void
    {
        $this->selectedUrl = $params['url'];
        $this->value = $params['url'];
        $this->closeModal($params['modalId']);
        $this->emit('fieldChange', ['name' => $this->name, 'value' => $url]);
        $this->refresh('media-picker-' . $this->name);
    }
}
```

### 5.4 UX-Live Bridge 桥接层

对于没有 `#[State]` 的 UX 组件（如 DatePicker、Rate、Transfer），通过桥接层实现与 Live 的双向同步：

```
UX 组件 (data-ux-model)
  ↓ ux:change 事件
UXLiveBridge
  ↓ 更新 hidden input + data-live-model
LiveComponent
  ↓ __updateProperty
服务端状态更新
  ↓ y:updated 事件
UXLiveBridge
  ↓ syncToUXComponent()
UX 组件更新显示
```

**桥接层工作流程**：

1. **PHP 端**：组件设置 `liveModel('property')`，渲染时添加 `data-ux-model="property"` 和隐藏 input
2. **JS 端**：组件交互触发 `ux:change` 事件
3. **Bridge**：捕获事件 → 更新隐藏 input 值 → 触发 `change` 事件 → Live 的 `data-live-model` 监听器捕获
4. **反向同步**：Live 更新完成 → `y:updated` 事件 → Bridge 检测 `data.patches` → 同步到 UX 组件

### 5.5 创建新 Live Field 的步骤

以创建一个 ColorPicker Live Field 为例：

```php
// 1. 继承 BaseField（而非 FormField）
class ColorPicker extends BaseField
{
    // 2. 定义 #[State] 属性
    #[State]
    public string $selectedColor = '';

    // 3. 定义 #[LiveAction] 方法
    #[LiveAction]
    public function pickColor(array $params): void
    {
        $this->selectedColor = $params['color'] ?? '#000000';
        $this->value = $this->selectedColor;

        $this->emit('fieldChange', [
            'name' => $this->name,
            'value' => $this->selectedColor,
        ]);

        $this->refresh('color-picker-' . $this->name);
    }

    // 4. 实现 render()
    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $container = Element::make('div')
            ->class('ux-form-color-picker')
            ->liveFragment('color-picker-' . $this->name);

        // ... 渲染颜色选择器 UI

        $wrapper->child($container);
        return $wrapper;
    }

    // 5. 实现 mount() 初始化状态
    public function mount(): void
    {
        $this->selectedColor = $this->getValue() ?? '#000000';
    }
}
```

**关键点**：
- 继承 `BaseField`（不是 `FormField`）
- 添加 `#[State]` 属性使 `isLiveComponent()` 返回 true
- 添加 `#[LiveAction]` 方法实现自包含交互
- 在 `mount()` 中从 `$this->getValue()` 初始化状态
- Action 中使用 `$this->emit()` 通知父组件值变更
- 使用 `$this->refresh()` 做 Fragment 级局部刷新

---

## 6. 前端注册机制

### 6.1 UX.register() — 组件注册中心

```javascript
// y-ux/index.js
const UXFramework = {
    _registry: new Map(),
    _initialized: new Set(),

    register(name, component) {
        if (this._registry.has(name)) return;  // 去重
        this._registry.set(name, component);

        // DOM 已就绪则立即初始化
        if (document.readyState !== 'loading') {
            this._initComponent(name, component);
        }
    },

    _initComponent(name, component) {
        if (this._initialized.has(name)) return;
        this._initialized.add(name);

        if (typeof component.init === 'function') {
            component.init();
        }
    },

    init() {
        // DOM 就绪后初始化所有已注册组件
        this._registry.forEach((component, name) => {
            this._initComponent(name, component);
        });

        // Hook 到 Live 系统
        if (window.L) this.hookLive(window.L);
    }
};

window.UX = UXFramework;
document.addEventListener('DOMContentLoaded', () => UXFramework.init());
```

### 6.2 PHP → JS 注册映射

PHP 端 `registerJs()` 生成的代码：

```javascript
UX.register('modal', (function() {
    const Modal = {
        open(id) { ... },
        close(id) { ... },
        init() { ... },
        liveHandler(op) { ... }
    };
    return Modal;
})());
```

### 6.3 hookLive() — Live 操作拦截

```javascript
hookLive(L) {
    const originalExecute = L.executeOperation;
    L.executeOperation = (op) => {
        // ux: 前缀的操作路由到 UX 组件
        if (op.op && op.op.startsWith('ux:')) {
            const componentName = op.op.split(':')[1];
            const component = this._registry.get(componentName);

            // 1. 优先使用组件的 liveHandler
            if (typeof component.liveHandler === 'function') {
                component.liveHandler(op);
                return;
            }

            // 2. 回退：按 action 名称直接调用组件方法
            const action = op.action;
            if (action && typeof component[action] === 'function') {
                component[action](op);
                return;
            }
        }
        // 非 ux: 操作交给原始处理器
        return originalExecute.call(L, op);
    };
}
```

### 6.4 UXLiveBridge — 双向同步

```javascript
const UXLiveBridge = {
    init() {
        // 监听 UX 组件的 ux:change → 同步到 Live
        document.addEventListener('ux:change', (e) => {
            this.handleUXChange(e.target, e.detail);
        });

        // 监听 Live 更新 → 同步到 UX 组件
        window.addEventListener('y:updated', (e) => {
            this.handleLiveUpdate(e.detail);
        });
    },

    handleUXChange(el, detail) {
        const uxModel = el.dataset.uxModel;
        if (!uxModel) return;

        const hiddenInput = this.ensureHiddenInput(el);
        hiddenInput.value = typeof detail.value === 'object'
            ? JSON.stringify(detail.value)
            : String(detail.value);

        // 触发 Live 的 data-live-model 监听器
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    },

    handleLiveUpdate(detail) {
        // 检查 patches，反向同步到 UX 组件
        if (!detail?.data?.patches) return;

        document.querySelectorAll('[data-ux-model]').forEach(uxEl => {
            const property = uxEl.dataset.uxModel;
            if (property in detail.data.patches) {
                this.syncToUXComponent(uxEl, property, detail.data.patches[property]);
            }
        });
    }
};
```

---

## 7. 完整对照表

### 7.1 表单字段对照

| 字段类型 | Components/ (Live) | Form/ (普通) | 有 #[State] | 有 #[LiveAction] | 自包含 |
|---------|-------------------|-------------|-------------|-----------------|--------|
| 文本输入 | TextInput | Input | ❌ | ❌ | ❌ |
| 多行文本 | Textarea | Textarea | ❌ | ❌ | ❌ |
| 下拉选择 | Select | Select | ❌ | ❌ | ❌ |
| 复选框 | Checkbox | Checkbox | ❌ | ❌ | ❌ |
| 单选组 | RadioGroup | RadioGroup | ❌ | ❌ | ❌ |
| 实时文本 | LiveTextInput | — | ✅ | ❌ | 部分 |
| 图片选择 | MediaPicker | — | ✅ | ✅ | ✅ |
| 链接选择 | LinkSelector | — | ✅ | ✅ | ✅ |
| 富文本 | — | RichEditor | ❌ | ❌ | ❌ |
| Live 富文本 | — | LiveRichEditor* | ✅ | ✅ | ✅ |
| 日期选择 | — | DatePicker | ❌ | ❌ | ❌ |
| 文件上传 | — | FileUpload | ❌ | ❌ | ❌ |

> *LiveRichEditor 继承 LiveComponent（非 UXLiveComponent），是独立的顶层 Live 组件。

### 7.2 注册机制对照

| 机制 | 适用对象 | 注册方式 | 作用 |
|------|---------|---------|------|
| `registerJs()` | UXComponent 子类 | `init()` 中调用 | 注册 JS 组件到 `UX.register()` |
| `registerCss()` | UXComponent 子类 | `init()` 中调用 | 注册内联 CSS |
| `registerComponent()` | FormBuilder | 静态方法 | 别名注册（预留） |
| `macro()` | FormBuilder | 静态方法 | 扩展 Builder 快捷方法 |
| `UX.register()` | 前端 JS | 自动 | 前端组件注册中心 |
| `hookLive()` | 前端 | 自动 | 拦截 `ux:` 前缀操作路由到 UX 组件 |
| `UXLiveBridge` | 前端 | 自动 | UX 组件 ↔ Live 双向值同步 |

### 7.3 渲染路径对照

```
FormBuilder.add($field)
  │
  ├─ $field 是 Live Field (isLiveComponent === true)
  │   ├─ setParent($this)
  │   ├─ _invoke() → mount()
  │   ├─ toHtml()
  │   │   ├─ getLiveMetadata() → JSON
  │   │   ├─ render() → Element
  │   │   └─ 包裹 <div data-live="..." data-live-state="...">
  │   └─ Element::make('div')->html(toHtml())
  │
  └─ $field 是普通 Field
      └─ render() → Element（直接使用）
```
