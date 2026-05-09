# Live 模块开发文档

> 框架的响应式组件系统，实现前后端状态同步、实时交互、流式输出等能力。

---

## 目录

1. [架构概览](#1-架构概览)
2. [核心组件](#2-核心组件)
3. [属性系统（Attribute）](#3-属性系统attribute)
4. [状态管理（Concerns）](#4-状态管理concerns)
5. [请求处理（LiveRequestHandler）](#5-请求处理liverequesthandler)
6. [响应构建（LiveResponse）](#6-响应构建liveresponse)
7. [事件系统](#7-事件系统)
8. [分片更新（Fragment）](#8-分片更新fragment)
9. [流式输出（Stream）](#9-流式输出stream)
10. [SSE 实时推送](#10-sse-实时推送)
11. [轮询机制（Poll）](#11-轮询机制poll)
12. [持久化状态（Persistent）](#12-持久化状态persistent)
13. [前端集成（y-live）](#13-前端集成y-live)
14. [安全机制](#14-安全机制)
15. [内置组件](#15-内置组件)
16. [完整示例](#16-完整示例)

---

## 1. 架构概览

### 1.1 整体架构

```
┌──────────────────────────────────────────────────────────────────┐
│                         前端 (y-live)                            │
│                                                                  │
│  data-live ──→ ReactiveState ──→ $live Proxy ──→ fetch /live/*  │
│  data-action ──→ parseAction ──→ dispatchAction                 │
│  data-live-model ──→ 双向绑定 ──→ dispatchState                 │
│  data-poll ──→ PollManager ──→ 定时 dispatchAction              │
│  SSE Client ──→ SseLive ──→ handleLiveAction/handleLiveState    │
│  data-navigate ──→ navigate() ──→ /live/navigate               │
└───────────────────────────┬──────────────────────────────────────┘
                            │ HTTP / SSE / Stream
┌───────────────────────────▼──────────────────────────────────────┐
│                      后端 (Live 模块)                             │
│                                                                  │
│  LiveRequestHandler (/live/*)                                    │
│    ├── /live/action    → 完整 Action 调用                        │
│    ├── /live/state     → 轻量状态更新                            │
│    ├── /live/event     → 事件分发到监听器                        │
│    ├── /live/stream    → 流式响应                                │
│    ├── /live/navigate  → 无刷新导航                              │
│    ├── /live/upload    → 文件上传                                │
│    └── /live/intl      → 国际化切换                              │
│                                                                  │
│  AbstractLiveComponent                                           │
│    ├── LiveComponent          (独立顶层组件)                      │
│    └── EmbeddedLiveComponent  (嵌入式子组件)                      │
│                                                                  │
│  Checksum (HMAC-SHA256 签名)                                    │
│  LiveEventBus (组件间事件总线)                                    │
│  LiveNotifier (外部推送通知)                                      │
│  LiveComponentResolver (测试桥接)                                │
└──────────────────────────────────────────────────────────────────┘
```

### 1.2 数据流

```
用户交互 → 前端 $live.action() → POST /live/action
    → LiveRequestHandler::handleAction()
    → resolveComponent() → deserializeState() → callAction()
    → serializeState() → diffPatches() → collectFragments()
    → JSON Response { patches, fragments, operations, events }
    → 前端 applyLiveResponse() → 更新 DOM
```

### 1.3 文件结构

```
framework/Component/Live/
├── AbstractLiveComponent.php      # 抽象基类
├── LiveComponent.php              # 独立顶层组件
├── EmbeddedLiveComponent.php      # 嵌入式子组件
├── Checksum.php                   # HMAC-SHA256 签名
├── LiveRequestHandler.php         # 请求处理（路由入口）
├── LiveResponse.php               # 响应构建器
├── LiveComponentResolver.php      # 测试桥接
├── LiveEventBus.php               # 组件间事件总线
├── LiveNotifier.php               # 外部推送通知
├── ConfirmDialog.php              # 确认对话框组件
├── LanguageSwitcherLive.php       # 语言切换器组件
├── Attribute/
│   ├── State.php                  # 状态属性
│   ├── Prop.php                   # 传入属性
│   ├── LiveAction.php             # 可调用方法
│   ├── LiveListener.php           # 事件监听
│   ├── Computed.php               # 计算属性
│   ├── Persistent.php             # 持久化
│   ├── Locked.php                 # 锁定属性
│   ├── Rule.php                   # 验证规则
│   ├── Session.php                # Session 驱动
│   ├── Cookie.php                 # Cookie 驱动
│   ├── LivePoll.php               # 轮询标记
│   ├── LiveSse.php                # SSE 标记
│   └── LiveStream.php             # 流式标记
├── Concerns/
│   ├── HasState.php               # 状态序列化/反序列化
│   ├── HasProperties.php          # 属性管理
│   ├── HasActions.php             # Action 管理
│   ├── HasOperations.php          # 操作队列
│   └── HasParentInjection.php     # 父组件注入
├── Stream/
│   ├── StreamBuilder.php          # 流式响应构建器
│   └── PollManager.php            # 轮询管理器
├── Sse/
│   ├── SseHub.php                 # SSE 中心推送
│   ├── SseEndpoint.php            # SSE 路由入口
│   ├── SseHelper.php              # SSE 视图助手
│   └── SseToken.php               # SSE 安全令牌
└── Persistent/
    ├── PersistentDriverInterface.php  # 持久化驱动接口
    ├── PersistentStateManager.php     # 持久化管理器
    ├── LocalStorageDriver.php         # 浏览器本地存储
    ├── DatabaseDriver.php             # 数据库存储
    ├── CacheDriver.php                # 缓存存储
    └── RedisDriver.php                # Redis 存储
```

---

## 2. 核心组件

### 2.1 AbstractLiveComponent

所有 Live 组件的抽象基类，组合了四个 Concern trait：

```php
abstract class AbstractLiveComponent
{
    use HasActions;       // Action 注册与调用
    use HasOperations;    // 操作队列（redirect, toast, modal 等）
    use HasProperties;    // 属性注入与填充
    use HasState;         // 状态序列化与反序列化
}
```

**关键方法：**

| 方法 | 说明 |
|------|------|
| `static::make(array $props, array $routeParams)` | 工厂方法，创建组件实例 |
| `named(string $name)` | 设置组件 ID |
| `init()` | 初始化（调用 mount） |
| `mount()` | 生命周期：组件挂载时调用 |
| `hydrate()` | 生命周期：状态反序列化后调用 |
| `onUpdate()` | 生命周期：状态更新后调用 |
| `dehydrate()` | 生命周期：状态序列化前调用（自动持久化） |
| `render()` | 渲染组件，返回 Element |
| `toHtml()` | 输出完整 HTML（含 data-live 属性） |
| `emit(string $event, array $params, ?string $targetId)` | 发射事件 |
| `refresh(string $name, string $mode)` | 标记分片刷新 |
| `validate(array $rules, array $data)` | 验证数据 |
| `checkAuth()` | 检查用户认证 |
| `checkRole(string\|array $roles)` | 检查用户角色 |
| `checkPermission(string\|array $permissions)` | 检查用户权限 |

**组件 ID 生成规则：** 基于类名自动生成，格式为 `短类名-序号`（如 `user-form-1`），可通过 `named()` 自定义。

### 2.2 LiveComponent

独立顶层组件，用于页面级或独立响应式区域：

```php
class LiveComponent extends AbstractLiveComponent
{
    public function toHtml(): string
    {
        // 输出格式：
        // <div data-live="ComponentClass" data-live-id="xxx" data-live-state="{...}">
        //   {render() 内容}
        // </div>
    }
}
```

**HTML 输出属性：**

| 属性 | 说明 |
|------|------|
| `data-live` | 组件完整类名 |
| `data-live-id` | 组件唯一 ID |
| `data-live-state` | JSON 元数据（含 __component, __id, __state, __props, __actions, __listeners） |
| `data-live-listeners` | 监听的事件列表（逗号分隔） |
| `data-loading` | 加载状态标记 |

### 2.3 EmbeddedLiveComponent

嵌入式子组件，可嵌套在父 LiveComponent 内，支持父子通信：

```php
abstract class EmbeddedLiveComponent extends AbstractLiveComponent
{
    use HasParentInjection;

    // 输出额外包含 data-live-parent-id 属性
}
```

**与 LiveComponent 的区别：**

| 特性 | LiveComponent | EmbeddedLiveComponent |
|------|---------------|----------------------|
| 层级 | 顶层独立 | 嵌入父组件内 |
| 父组件 | 无 | 有（通过 HasParentInjection） |
| HTML 输出 | 无 parent-id | 含 `data-live-parent-id` |
| 典型场景 | 页面级组件 | 表单字段、子区域 |

**检测 Live 组件：**

```php
// 判断一个 EmbeddedLiveComponent 实例是否包含 #[State] 或 #[LiveAction]
EmbeddedLiveComponent::isLiveComponent($component); // bool
```

---

## 3. 属性系统（Attribute）

### 3.1 #[State] — 状态属性

标记公开属性为可序列化的组件状态，自动与前端同步：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class State
{
    public function __construct(
        public mixed $default = null,        // 默认值
        public bool $frontendEditable = true, // 前端是否可编辑
    ) {}
}
```

**用法：**

```php
class Counter extends LiveComponent
{
    #[State(default: 0)]
    public int $count = 0;

    #[State(frontendEditable: false)]  // 前端只读
    public string $secret = 'hidden';
}
```

**`frontendEditable` 规则：**
- `true`（默认）：前端可通过 `data-live-model` 直接修改
- `false`：前端不可直接修改，需通过 Action 间接修改；修改时后端会校验 checksum

### 3.2 #[Prop] — 传入属性

从父组件或路由参数注入的属性：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Prop
{
    public function __construct(
        public mixed $default = null,       // 默认值
        public ?string $fromRoute = null,   // 从路由参数取值
        public bool $required = false,      // 是否必须
    ) {}
}
```

**注入优先级：** `make() 传入 > routeParams(fromRoute) > routeParams(同名) > default`

```php
class UserCard extends EmbeddedLiveComponent
{
    #[Prop(required: true)]
    public int $userId;

    #[Prop(fromRoute: 'id')]
    public int $id;

    #[Prop(default: 'guest')]
    public string $name;
}
```

### 3.3 #[LiveAction] — 可调用方法

标记方法可被前端调用：

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveAction
{
    public function __construct(
        public ?string $name = null,  // 自定义 Action 名称
    ) {}
}
```

```php
#[LiveAction]
public function increment(): void
{
    $this->count++;
}

#[LiveAction(name: 'submit-form')]  // 自定义名称
public function handleSubmit(array $params): void
{
    // ...
}
```

**Action 参数解析规则：**
- 单个 `array $params` 参数：接收全部参数
- 具名参数：按参数名从请求中匹配
- 支持类型转换：int, float, bool, string, array

### 3.4 #[LiveListener] — 事件监听

标记方法为事件监听器：

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveListener
{
    public function __construct(
        public string $event,     // 监听的事件名
        public int $priority = 0, // 优先级
    ) {}
}
```

```php
#[LiveListener(event: 'order.created')]
public function onOrderCreated(array $params): void
{
    $this->orderId = $params['orderId'];
    $this->refresh('order-info');
}
```

### 3.5 #[Computed] — 计算属性

标记方法为计算属性（当前为标记用途）：

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class Computed
{
    public function __construct(
        public ?string $name = null,
    ) {}
}
```

### 3.6 #[Locked] — 锁定属性

标记属性为前端不可修改，即使 `#[State(frontendEditable: true)]` 也无法覆盖：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Locked
{
    public function __construct(
        public string $reason = '',  // 锁定原因
    ) {}
}
```

```php
#[State]
#[Locked(reason: '服务端计算，前端不可修改')]
public int $totalPrice = 0;
```

### 3.7 #[Rule] — 验证规则

为属性附加验证规则：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Rule
{
    public function __construct(
        public string $rules,  // 验证规则字符串
    ) {}
}
```

```php
#[State]
#[Rule('required|email')]
public string $email = '';

#[State]
#[Rule('required|min:6')]
public string $password = '';
```

### 3.8 #[Session] — Session 驱动属性

属性值存储在 Session 中：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Session
{
    public function __construct(
        public ?string $key = null,  // Session 键名
    ) {}
}
```

```php
#[Session]
public string $lastViewedCategory = '';
```

### 3.9 #[Cookie] — Cookie 驱动属性

属性值存储在 Cookie 中：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Cookie
{
    public function __construct(
        public ?string $key = null,
        public int $minutes = 1440,       // 过期时间（分钟）
        public ?string $path = null,
        public ?string $domain = null,
        public ?bool $secure = null,
        public bool $httpOnly = true,
    ) {}
}
```

```php
#[Cookie(minutes: 43200)]  // 30 天
public string $theme = 'light';
```

### 3.10 #[Persistent] — 持久化属性

属性值持久化到指定存储驱动：

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Persistent
{
    public function __construct(
        public string $driver = 'local',  // 驱动：local, database, cache, redis
        public ?string $key = null,       // 存储键名
        public ?int $ttl = null,          // 过期时间（秒）
        public bool $encrypt = false,     // 是否加密
    ) {}
}
```

```php
#[Persistent('local')]
public string $locale = 'en';

#[Persistent('database', ttl: 86400, encrypt: true)]
public array $userPreferences = [];
```

### 3.11 #[LivePoll] — 轮询标记

标记方法为可被前端定时轮询的 Action：

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class LivePoll
{
    public function __construct(
        public int $interval = 5000,       // 轮询间隔（毫秒）
        public bool $immediate = true,     // 是否立即执行
        public string $condition = '',     // JS 条件表达式
    ) {}
}
```

```php
#[LivePoll(interval: 2000, condition: 'status !== "completed"')]
public function checkStatus(): array
{
    return [
        'progress' => $this->task->progress,
        'status' => $this->task->status,
    ];
}
```

### 3.12 #[LiveSse] — SSE 标记

标记方法返回 SSE 长连接响应：

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveSse
{
    public function __construct(
        public int $keepAlive = 30,     // 心跳间隔（秒）
        public array $channels = [],    // 订阅频道
    ) {}
}
```

### 3.13 #[LiveStream] — 流式标记

标记方法返回流式响应：

```php
#[\Attribute(\Attribute::TARGET_METHOD)]
class LiveStream
{
    public function __construct(
        public string $format = 'ndjson',  // 输出格式：ndjson, sse, text
    ) {}
}
```

---

## 4. 状态管理（Concerns）

### 4.1 HasState — 状态序列化

**核心流程：**

```
serializeState():
  1. 收集所有非公开、非内部属性
  2. 收集公开属性（#[State]/#[Prop]/#[Session]/#[Cookie]/#[Persistent]）
  3. 计算公开属性 checksum
  4. 计算 #[Locked] 属性的 checksum
  5. serialize() → Checksum::seal() → base64 编码

deserializeState():
  1. base64 解码 → Checksum::unseal() → unserialize()
  2. 恢复非公开属性
  3. 恢复 Session/Cookie/Persistent 驱动的公开属性
  4. 恢复 _raw 原始数据
  5. 调用 hydrate() 生命周期
```

**Checksum 签名机制：**

```php
// seal: serialize → gzcompress → HMAC-SHA256 签名 → base64
Checksum::seal(string $componentClass, string $serialized): string

// unseal: base64 → 验证签名 → gzuncompress → 返回原始数据
Checksum::unseal(string $componentClass, string $sealed): string
```

签名密钥从 `config('app.key')` 派生，使用 HMAC-SHA256 确保状态不可被篡改。

### 4.2 HasProperties — 属性管理

**属性分类：**

| 类型 | 标记 | 前端可编辑 | 序列化 |
|------|------|-----------|--------|
| State | `#[State]` | 由 `frontendEditable` 决定 | ✅ |
| Prop | `#[Prop]` | 否（需 checksum 校验） | ✅ |
| Session | `#[Session]` | 否 | ✅ |
| Cookie | `#[Cookie]` | 否 | ✅ |
| Persistent | `#[Persistent]` | 否 | ✅ |
| Locked | `#[Locked]` | ❌ 不可修改 | ✅ |

**fillPublicProperties() 安全检查：**
1. 只允许 `allowedStateProperties()` 中的属性
2. `#[Locked]` 属性拒绝修改
3. 非 `frontendEditable` 属性需通过 checksum 校验
4. 校验失败抛出 `RuntimeException`

### 4.3 HasActions — Action 管理

**Action 注册来源：**
1. `#[LiveAction]` 属性标记的方法
2. `#[LivePoll]` 属性标记的方法
3. `registerAction()` / `registerActions()` 手动注册
4. 内置 Action：`__updateProperty`、`__refresh`

**callAction() 调用流程：**
```
callAction(actionName, params)
  → getLiveActions() 获取注册表
  → 查找 handler（string 方法名 或 [ClassName, methodName]）
  → normalizeActionParams() 参数规范化
  → 反射解析方法参数，类型转换
  → 调用方法，返回结果
```

**外部类方法引用：**

```php
$this->registerAction('export', [ExportService::class, 'handle']);
// ExportService::handle 必须标记 #[LiveAction]
// 调用时传入 ($this, $params)
```

### 4.4 HasOperations — 操作队列

组件可通过操作队列向前端发送指令，自动去重：

| 方法 | 操作类型 | 说明 |
|------|---------|------|
| `redirect(string $url)` | redirect | 页面跳转 |
| `navigateTo(string $url, ...)` | navigate | 无刷新导航 |
| `refreshPage()` | reload | 刷新页面 |
| `dispatchEvent(string $event, array $detail)` | dispatch | 派发前端事件 |
| `loadScript(string $src)` | loadScript | 加载外部 JS |
| `loadScriptIds(string\|array $ids)` | loadScript | 加载注册的 JS 片段 |
| `openModal(string $id)` | ux:modal | 打开模态框 |
| `closeModal(string $id)` | ux:modal | 关闭模态框 |
| `toast(string $msg, string $type, ...)` | ux:toast | 显示提示 |
| `confirm(string $msg, ...)` | confirm | 确认对话框 |
| `loading(string $target)` | loading | 显示加载状态 |
| `loadingEnd(string $target)` | loading:end | 结束加载状态 |
| `replace()` | replace | 替换组件完整 HTML |
| `replaceElement(string $selector, ...)` | replaceElement | 替换指定元素 |
| `addChild(string $selector, ...)` | addChild | 添加子元素 |
| `updateAttribute(string $selector, ...)` | updateAttribute | 更新元素属性 |
| `trigger(string $targetAction, ...)` | trigger | 触发另一组件方法 |
| `ux(string $component, ...)` | ux:{component} | UX 组件操作 |

### 4.5 HasParentInjection — 父组件注入

嵌入式组件可访问父组件：

```php
trait HasParentInjection
{
    protected ?LiveComponent $parent = null;

    public function setParent(LiveComponent $parent): void
    public function getParent(): ?LiveComponent
    public function hasParent(): bool
    public function dispatchToParent(string $action, array $params): mixed
}
```

---

## 5. 请求处理（LiveRequestHandler）

### 5.1 路由端点

所有端点在 `/live` 路由组下，受 CSRF 保护：

| 端点 | 方法 | 说明 |
|------|------|------|
| `/live/action` | POST | 完整 Action 调用 |
| `/live/state` | POST | 轻量状态属性更新 |
| `/live/event` | POST | 事件分发到监听器 |
| `/live/stream` | POST | 流式响应 |
| `/live/navigate` | POST | 无刷新导航 |
| `/live/upload` | POST | 文件上传 |
| `/live/intl` | POST | 国际化切换 |
| `/live/update` | POST | 已废弃，兼容 `/live/action` |
| `/live/sse/{token}` | GET | SSE 长连接 |

### 5.2 handleAction() 流程

```
1. extractParams()       — 从请求提取组件类、Action、状态、数据
2. guardComponentClass() — 安全校验（类存在性、白名单）
3. resolveComponent()    — 从容器创建组件实例
4. registerPeerComponents() — 注册同页面的其他组件到 LiveEventBus
5. injectParentComponent()  — 注入父组件（EmbeddedLiveComponent）
6. deserializeState()    — 反序列化状态
7. fillPublicProperties() — 填充前端数据
8. _invokeAction()       — 触发属性注入
9. callAction()          — 调用目标 Action
10. diffPatches()        — 计算状态差异
11. collectEmittedEvents() — 处理事件广播
12. collectManualUpdates() — 处理手动更新
13. collectFragments()   — 收集分片更新
14. collectActionResult() — 收集 LiveResponse 操作
15. collectComponentOperations() — 收集组件操作
16. 返回 JSON 响应
```

### 5.3 handleStateUpdate() 流程

轻量级状态更新，不调用 Action：

```
1. resolveComponent()
2. deserializeState()
3. fillPublicProperties()
4. onUpdate()  — 生命周期钩子
5. diffPatches()
6. 返回 JSON { state, patches, events }
```

### 5.4 handleEvent() 流程

事件分发到 `#[LiveListener]`：

```
1. resolveComponent()
2. deserializeState()
3. findEventHandler() — 查找匹配的监听器方法
4. 调用监听器方法
5. diffPatches()
6. 返回 JSON { state, patches }
```

### 5.5 组件白名单

可通过配置限制可实例化的组件类：

```php
// config/live.php
return [
    'component_whitelist' => [
        'App\\Live\\',           // 命名空间前缀匹配
        'Admin\\Pages\\Dashboard', // 精确匹配
    ],
];

// 或静态设置
LiveRequestHandler::setComponentWhitelist(['App\\Live\\']);
```

---

## 6. 响应构建（LiveResponse）

### 6.1 创建与链式调用

```php
return LiveResponse::make()
    ->toast('保存成功', 'success')
    ->closeModal('edit-modal')
    ->navigateTo('/users');
```

### 6.2 操作方法

| 方法 | 说明 |
|------|------|
| `update(string $field, mixed $value)` | 更新字段值 |
| `html(string $selector, string $html)` | 替换元素 HTML |
| `domPatch(string $selector, string $html)` | DOM 补丁 |
| `append(string $selector, string $html)` | 追加 HTML |
| `remove(string $selector)` | 移除元素 |
| `addClass(string $selector, string $class)` | 添加 CSS 类 |
| `removeClass(string $selector, string $class)` | 移除 CSS 类 |
| `toast(string $msg, string $type, ...)` | 显示提示 |
| `notify(string $title, string $msg, ...)` | 显示通知 |
| `openModal(string $id)` | 打开模态框 |
| `closeModal(string $id)` | 关闭模态框 |
| `redirect(string $url)` | 页面跳转 |
| `navigateTo(string $url, ...)` | 无刷新导航 |
| `reload()` | 刷新页面 |
| `dispatch(string $event, ...)` | 派发前端事件 |
| `fragment(string $name, string $html, ...)` | 分片更新 |
| `fragments(array $fragments)` | 批量分片更新 |

**注意：** `js()` 方法已禁用，出于安全考虑，使用 `dispatch()` 替代。

### 6.3 toArray() 输出

```php
[
    'operations' => [...],   // 操作列表
    'domPatches' => [...],   // DOM 补丁
    'fragments'  => [...],   // 分片更新
]
```

---

## 7. 事件系统

### 7.1 组件内事件（emit）

组件内部发射事件，通知其他组件：

```php
#[LiveAction]
public function createOrder(): void
{
    // ... 创建订单逻辑
    $this->emit('order.created', ['orderId' => $order->id]);
    $this->emit('stats.updated', [], 'stats-panel');  // 定向发送
}
```

**emit 参数：**
- `event`：事件名称
- `params`：事件参数
- `targetId`：目标组件 ID（可选，定向发送）

### 7.2 事件总线（LiveEventBus）

请求周期内的组件间通信：

```php
// 注册组件状态（LiveRequestHandler 自动调用）
LiveEventBus::storeComponentState($componentId, $class, $state);

// 查找事件监听器
LiveEventBus::findListenersForEvent('order.created', $excludeComponentId);

// 定向查找
LiveEventBus::findListenerForComponent($componentId, 'order.created');

// 重置（每次请求开始时）
LiveEventBus::reset();
```

### 7.3 外部推送（LiveNotifier）

从 Live 组件外部推送更新到前端：

```php
// SSE 推送：触发组件 Action
LiveNotifier::action('user-dashboard', 'refreshData', ['limit' => 10]);

// SSE 推送：直接更新组件属性
LiveNotifier::state('notification-badge', ['count' => 5]);

// SSE 推送：批量更新
LiveNotifier::batch([
    ['componentId' => 'badge', 'action' => 'refresh'],
    ['componentId' => 'list', 'action' => 'reload', 'params' => ['page' => 1]],
]);

// SSE 推送：广播
LiveNotifier::broadcast('orders', ['event' => 'new_order', 'id' => 123]);

// SSE 推送：指定用户
LiveNotifier::toUser(1, 'notifications', ['message' => '新消息']);

// 同步事件（当前请求周期内）
LiveNotifier::emit('order.created', ['orderId' => 123]);
```

### 7.4 事件处理流程

```
子组件 emit('order.created', [...])
  → 存入 $emittedEvents
  → LiveRequestHandler::collectEmittedEvents()
    → 定向事件：findListenerForComponent() → processComponentUpdate()
    → 广播事件：findListenersForEvent() → 逐个 processComponentUpdate()
      → 反序列化目标组件 → 调用监听器方法 → 序列化新状态
      → 加入 response.componentUpdates[]
```

---

## 8. 分片更新（Fragment）

### 8.1 概念

分片更新允许只刷新组件的部分区域，而非整个组件，减少网络传输和 DOM 操作。

### 8.2 后端标记

在 `render()` 中使用 `liveFragment()` 标记分片：

```php
public function render(): Element
{
    return Element::make('div')
        ->children([
            Element::make('div')
                ->liveFragment('user-info')
                ->children([...]),

            Element::make('div')
                ->liveFragment('order-list')
                ->children([...]),
        ]);
}
```

### 8.3 触发分片刷新

在 Action 中调用 `refresh()`：

```php
#[LiveAction]
public function updateUser(): void
{
    // ... 更新逻辑
    $this->refresh('user-info');  // 只刷新 user-info 分片
}

#[LiveAction]
public function addOrder(): void
{
    // ... 添加逻辑
    $this->refresh('order-list', 'append');  // 追加模式
}
```

**刷新模式：**
- `replace`（默认）：替换分片内容
- `append`：追加到分片末尾
- `prepend`：插入到分片开头

### 8.4 LiveResponse 分片

也可以通过 LiveResponse 返回分片：

```php
#[LiveAction]
public function updateStats(): LiveResponse
{
    return LiveResponse::make()
        ->fragment('stats-panel', $this->renderStats());
}
```

### 8.5 FragmentRegistry

分片注册表，在 render() 过程中收集分片信息：

```php
FragmentRegistry::getInstance()->reset();
FragmentRegistry::getInstance()->setTargets($refreshTargets);
$component->render();
$fragments = FragmentRegistry::getInstance()->getFragments();
// 返回 ['name' => ['element' => Element, 'mode' => 'replace'], ...]
```

---

## 9. 流式输出（Stream）

### 9.1 StreamBuilder

流式响应构建器，支持 AI 对话、进度条等场景：

```php
return StreamBuilder::create()
    ->thinking('正在思考...')
    ->each($this->generateTokens(), fn($token) => StreamBuilder::textChunk($token))
    ->done(['message' => $message])
    ->build();
```

### 9.2 构建方法

| 方法 | 说明 |
|------|------|
| `create()` | 创建构建器 |
| `format(string $format)` | 设置输出格式（ndjson/sse/text） |
| `text(string $content)` | 添加文本块 |
| `html(string $html)` | 添加 HTML 块 |
| `json(array $data)` | 添加 JSON 数据块 |
| `progress(int $current, int $total, ...)` | 添加进度更新 |
| `error(string $message, int $code)` | 添加错误块 |
| `done(array $data)` | 添加完成标记 |
| `thinking(string $thought)` | 添加 AI 思考状态 |
| `toolCall(string $tool, array $args)` | 添加工具调用 |
| `each(iterable $items, callable $cb)` | 延迟遍历（Generator 在 send 时执行） |
| `raw(array $item)` | 添加原始数据块 |
| `when(bool $condition, callable $cb)` | 条件添加 |
| `build()` | 构建 StreamResponse |

### 9.3 静态块工厂

```php
StreamBuilder::textChunk('hello');           // 文本块
StreamBuilder::progressChunk(50, 100);       // 进度块
StreamBuilder::doneChunk(['result' => 'ok']); // 完成块
StreamBuilder::errorChunk('出错了');          // 错误块
```

### 9.4 使用示例

```php
#[LiveStream(format: 'ndjson')]
public function chatStream(): StreamResponse
{
    return StreamBuilder::create()
        ->thinking('分析问题中...')
        ->each($this->generateTokens(), fn($token) => StreamBuilder::textChunk($token))
        ->done(['usage' => $this->getUsage()])
        ->build();
}

#[LiveStream(format: 'ndjson')]
public function exportProgress(): StreamResponse
{
    return StreamBuilder::create()
        ->text('开始导出...')
        ->each(range(1, 100), fn($i) => StreamBuilder::progressChunk($i, 100))
        ->done(['url' => $this->getExportUrl()])
        ->build();
}
```

---

## 10. SSE 实时推送

### 10.1 架构

```
SseHub (后端推送)
  → JSONL 文件存储消息
  → SseEndpoint (/live/sse/{token})
    → 验证 Token → 订阅频道 → 轮询消息 → 推送 SSE 事件
  → SseLive (前端)
    → 收到 live:action → L.dispatch() → 标准 /live/action 流程
    → 收到 live:state → 直接 merge state
```

### 10.2 SseHub 推送方法

```php
// 推送到频道
SseHub::push('notifications', ['message' => '新消息']);

// 推送给指定用户
SseHub::toUser(123, 'private', ['data' => '...']);

// 触发 LiveAction
SseHub::liveAction('user-dashboard', 'refreshData', ['limit' => 10]);

// 批量更新
SseHub::liveBatch([
    ['componentId' => 'badge', 'action' => 'refresh'],
]);

// 推送状态更新
SseHub::liveState('notification-badge', ['count' => 5]);
```

### 10.3 SseToken 安全机制

- 绑定 Session ID，防止跨用户使用
- 可绑定用户 ID
- 默认 24 小时过期
- 可限制订阅频道
- HMAC 签名验证

### 10.4 前端集成

```php
// 在页面中注入 SSE 配置
$document->inject('head', SseHelper::metaElement(['notifications', 'orders']));

// 在 Element 上添加订阅
SseHelper::subscribe($element, 'notifications', 'orders');
```

### 10.5 SseClient 前端配置

```javascript
// 自动初始化（从 meta[name="sse-config"] 读取）
SseLive.init();

// 手动连接
SseLive.init({
    token: '...',
    endpoint: '/live/sse/...',
    channels: ['notifications']
});
```

---

## 11. 轮询机制（Poll）

### 11.1 后端标记

```php
#[LivePoll(interval: 3000, immediate: true, condition: 'status !== "completed"')]
public function checkProgress(): array
{
    return [
        'progress' => $this->task->progress,
        'status' => $this->task->status,
    ];
}
```

### 11.2 前端自动发现

```html
<div data-poll='{"checkProgress": {"interval": 3000}}'>
    进度: {{ progress }}%
</div>
```

### 11.3 PollManager API

```php
// 注册轮询
PollManager::register($componentId, 'checkStatus', 2000);

// 获取配置
PollManager::get($componentId);

// 生成前端 JSON
PollManager::toJson($componentId);

// 清除
PollManager::clear($componentId);
```

### 11.4 前端 Poll 对象

```javascript
// 自动初始化
Poll.autoInit(document);

// 手动启动
Poll.fromLiveAction(liveEl, 'checkStatus', {
    interval: 2000,
    immediate: true,
    condition: 'status !== "completed"'
});

// 停止
Poll.stop('component-id:checkStatus');
Poll.stopAll();
```

### 11.5 对比

| 特性 | Poll | SSE | Stream |
|------|------|-----|--------|
| 连接方式 | 多次短连接 | 单长连接 | 单流连接 |
| 服务器压力 | 较高 | 低 | 低 |
| 实时性 | 有延迟 | 即时 | 即时 |
| 兼容性 | 最好 | 好 | 好 |
| 实现复杂度 | 最简单 | 中等 | 中等 |

---

## 12. 持久化状态（Persistent）

### 12.1 驱动接口

```php
interface PersistentDriverInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function forget(string $key): bool;
    public function has(string $key): bool;
}
```

### 12.2 内置驱动

| 驱动 | 类名 | 说明 |
|------|------|------|
| `local` | LocalStorageDriver | 浏览器 localStorage（需前端配合） |
| `database` | DatabaseDriver | 数据库 `component_states` 表 |
| `cache` | CacheDriver | 框架缓存系统 |
| `redis` | RedisDriver | Redis 键值存储 |

### 12.3 PersistentStateManager

```php
// 注册自定义驱动
PersistentStateManager::registerDriver('custom', CustomDriver::class);

// 获取驱动
$driver = PersistentStateManager::getDriver('database');

// 同步属性到持久化存储
PersistentStateManager::syncPersistentProperty($component, 'locale');

// 从持久化存储恢复属性
PersistentStateManager::restorePersistentProperty($component, 'locale');

// 获取所有持久化属性名
PersistentStateManager::getAllPersistentProperties($component);
```

### 12.4 生命周期

```
组件创建 → mount() → restoreDrivenProperties()（恢复 Session/Cookie/Persistent）
组件销毁 → dehydrate() → persistProperties()（同步 Persistent 属性）
```

### 12.5 加密存储

```php
#[Persistent('database', encrypt: true, ttl: 3600)]
public array $sensitiveData = [];
```

使用 AES-256-CBC 加密，密钥从 `config('app.key')` 派生。

### 12.6 前端持久化

前端 `persistent.js` 处理 `local` 和 `session` 驱动：

```javascript
// API
window.$persistent.set(key, value, driver, ttl);
window.$persistent.get(key, driver);
window.$persistent.remove(key, driver);
window.$persistent.clear();
```

---

## 13. 前端集成（y-live）

### 13.1 指令系统

| 指令 | 说明 | 示例 |
|------|------|------|
| `data-live` | 标记 Live 组件 | `data-live="UserForm"` |
| `data-action` | 绑定 Action | `data-action:click="save()"` |
| `data-live-model` | 双向绑定 | `data-live-model.live="email"` |
| `data-submit` | 表单提交 | `data-submit:click="saveSettings"` |
| `data-live-upload` | 文件上传 | `data-live-upload` |
| `data-poll` | 轮询配置 | `data-poll='{"checkStatus":{"interval":2000}}'` |
| `data-navigate` | 无刷新导航 | `data-navigate` (a 标签) |
| `data-live-sse` | SSE 订阅 | `data-live-sse="notifications"` |
| `data-live-fragment` | 分片标记 | `data-live-fragment="user-info"` |
| `data-persistent` | 持久化配置 | `data-persistent='{"locale":{"driver":"local"}}'` |

### 13.2 $live Proxy

每个 Live 组件 DOM 元素上挂载 `$live` 代理对象：

```javascript
// 调用 Action
el.$live.increment()
el.$live.save({ name: 'test' })

// 更新属性（走 /live/state）
await el.$live.update('count', 5)

// 刷新分片
el.$live.refresh('user-info')

// 派发前端事件
el.$live.dispatch('custom-event', { detail: '...' })

// 草稿模式（延迟同步）
el.$live.setDraft('title', '新标题')  // 只更新本地
await el.$live.commitDraft()            // 批量提交到后端

// 访问父组件
el.$live.$parent.someAction()

// 获取状态
el.$live.get()  // → { count: 5, name: 'test' }

// 加载状态
el.$live.loading  // → true/false
```

### 13.3 data-action 参数解析

支持位置参数和命名参数：

```html
<button data-action:click="increment()">+1</button>
<button data-action:click="setCount(5)">Set 5</button>
<button data-action:click="save(name: 'test', age: 25)">Save</button>
<button data-action:click="remove(1, force: true)">Remove</button>
```

参数类型自动推断：字符串、数字、布尔值、null。

### 13.4 data-live-model 修饰符

```html
<!-- 实时同步到后端 -->
<input data-live-model.live="email">

<!-- 失焦时同步 -->
<input data-live-model.live.blur="email">

<!-- 防抖（默认 300ms） -->
<input data-live-model.live.debounce.500="search">

<!-- 不同步后端，仅本地响应式 -->
<input data-live-model="localOnly">
```

### 13.5 DOM 更新机制

```
后端响应 → applyLiveResponse()
  → patches: state.merge() 更新响应式状态
  → domPatches: replaceLiveHtml() 替换指定元素
  → fragments: applyLiveFragment() 替换分片
  → operations: executeOperation() 执行操作
  → events: processEvents() 处理事件
```

**DOM 安全：**
- 禁止 `<script>`, `<iframe>`, `<object>`, `<embed>` 标签
- 移除 `on*` 事件属性
- 过滤 `javascript:` URL

### 13.6 无刷新导航

```html
<!-- 自动绑定 -->
<a href="/users" data-navigate>用户列表</a>

<!-- 替换模式 -->
<a href="/users/1" data-navigate data-navigate-replace>用户详情</a>

<!-- 指定分片 -->
<a href="/dashboard" data-navigate data-navigate-fragment="main-content">仪表盘</a>
```

后端页面使用 `data-navigate-fragment` 标记可替换区域。

---

## 14. 安全机制

### 14.1 状态签名

所有序列化状态都经过 HMAC-SHA256 签名：

```php
Checksum::seal($componentClass, $serialized)
// → gzcompress → HMAC-SHA256 签名 → base64

Checksum::unseal($componentClass, $sealed)
// → base64 → 验证签名 → gzuncompress
```

签名失败抛出 `RuntimeException`，防止状态篡改。

### 14.2 CSRF 保护

所有 POST 端点受 CSRF 中间件保护：

```php
#[Route('/action', ['POST'], name: 'live.action', middleware: [VerifyCsrfToken::class])]
```

前端自动从 `meta[name="csrf-token"]` 读取 Token 并附加到请求头。

### 14.3 组件白名单

限制可实例化的组件类，防止任意类实例化：

```php
// 配置
'component_whitelist' => ['App\\Live\\', 'Admin\\Pages\\'],

// 校验
LiveRequestHandler::guardComponentClass($class);
// → 检查类存在性
// → 检查是否继承 AbstractLiveComponent
// → 检查白名单（精确匹配或命名空间前缀匹配）
```

### 14.4 #[Locked] 属性保护

前端无法修改被 `#[Locked]` 标记的属性：

```php
#[State]
#[Locked(reason: '服务端计算')]
public int $totalPrice = 0;
```

即使前端提交了修改值，`fillPublicProperties()` 也会拒绝。

### 14.5 frontendEditable 校验

非 `frontendEditable` 的属性，前端提交时需通过 checksum 校验：

```php
#[State(frontendEditable: false)]
public string $role = 'user';
```

前端如果修改了此值，checksum 不匹配会抛出异常。

### 14.6 DOM 净化

前端 `sanitizeLiveTree()` 确保动态插入的 HTML 安全：
- 移除危险标签（script, iframe 等）
- 移除 on* 事件属性
- 过滤 javascript: URL

### 14.7 SSE Token 安全

- 绑定 Session ID
- 可绑定用户 ID
- 过期时间限制
- 频道权限控制
- HMAC 签名验证

---

## 15. 内置组件

### 15.1 ConfirmDialog

确认对话框组件：

```php
// 渲染
echo live('confirm-dialog', id: 'delete-confirm');

// 触发确认
button('删除')->confirm('delete-confirm', action: 'destroy', data: ['id' => 1]);
```

**Action：**
- `show(array $params)` — 显示对话框
- `hide()` — 隐藏对话框
- `accept()` — 确认操作，发射 `confirm:accepted` 事件

### 15.2 LanguageSwitcherLive

语言切换器组件，使用 `#[Persistent('local')]` 持久化语言偏好：

```php
// 在 LiveComponent 中渲染
public function render(): Element
{
    return Element::make('div')->children(
        Element::make('h1')->intl('site.title'),
        new LanguageSwitcherLive()
    );
}
```

**Action：**
- `switchLocale(string $locale)` — 切换语言，设置 Cookie 并刷新页面

---

## 16. 完整示例

### 16.1 计数器

```php
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\View\Base\Element;

class Counter extends LiveComponent
{
    #[State(default: 0)]
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
    }

    #[LiveAction]
    public function decrement(): void
    {
        $this->count--;
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->count = 0;
        $this->toast('已重置', 'info');
    }

    public function render(): Element
    {
        return Element::make('div')
            ->class('counter')
            ->children([
                Element::make('button')
                    ->data('action', 'decrement')
                    ->text('-'),
                Element::make('span')
                    ->text((string) $this->count),
                Element::make('button')
                    ->data('action', 'increment')
                    ->text('+'),
                Element::make('button')
                    ->data('action', 'reset')
                    ->text('重置'),
            ]);
    }
}
```

### 16.2 带验证的表单

```php
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\Rule;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\LiveResponse;
use Framework\View\Base\Element;

class ContactForm extends LiveComponent
{
    #[State]
    #[Rule('required|email')]
    public string $email = '';

    #[State]
    #[Rule('required|min:3')]
    public string $message = '';

    #[State(frontendEditable: false)]
    public bool $submitted = false;

    #[LiveAction]
    public function submit(): LiveResponse
    {
        if (!$this->validate()) {
            return LiveResponse::make()
                ->toast('请检查表单', 'error');
        }

        // 处理表单...
        $this->submitted = true;

        return LiveResponse::make()
            ->toast('提交成功！', 'success')
            ->navigateTo('/thank-you');
    }

    public function render(): Element
    {
        return Element::make('form')
            ->attr('data-submit:click', 'submit')
            ->children([
                Element::make('input')
                    ->attr('type', 'email')
                    ->attr('data-live-model.live', 'email')
                    ->attr('value', $this->email),
                Element::make('textarea')
                    ->attr('data-live-model.live', 'message')
                    ->text($this->message),
                Element::make('button')
                    ->attr('type', 'submit')
                    ->text('提交'),
            ]);
    }
}
```

### 16.3 嵌入式子组件

```php
use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\Prop;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\View\Base\Element;

class MediaPicker extends EmbeddedLiveComponent
{
    #[Prop(required: true)]
    public string $field = '';

    #[State]
    public string $value = '';

    #[State]
    public bool $pickerOpen = false;

    #[LiveAction]
    public function openPicker(): void
    {
        $this->pickerOpen = true;
    }

    #[LiveAction]
    public function closePicker(): void
    {
        $this->pickerOpen = false;
    }

    #[LiveAction]
    public function selectMedia(array $params): void
    {
        $this->value = $params['url'];
        $this->pickerOpen = false;
        $this->emit('media.selected', [
            'field' => $this->field,
            'url' => $this->value,
        ]);
    }

    #[LiveListener(event: 'media.selected')]
    public function onMediaSelected(array $params): void
    {
        if ($params['field'] === $this->field) {
            $this->value = $params['url'];
        }
    }

    public function render(): Element
    {
        $el = Element::make('div')
            ->class('media-picker')
            ->children([
                Element::make('input')
                    ->attr('type', 'hidden')
                    ->attr('name', $this->field)
                    ->attr('value', $this->value),
                Element::make('button')
                    ->data('action', 'openPicker')
                    ->text('选择媒体'),
            ]);

        if ($this->pickerOpen) {
            $el->child(
                Element::make('div')
                    ->class('picker-modal')
                    ->children([...])
            );
        }

        return $el;
    }
}
```

### 16.4 SSE 实时通知

```php
// 后端推送
use Framework\Component\Live\LiveNotifier;

class OrderService
{
    public function createOrder(array $data): Order
    {
        $order = Order::create($data);

        LiveNotifier::action('order-list', 'refresh');
        LiveNotifier::state('notification-badge', [
            'count' => Notification::unreadCount(),
        ]);
        LiveNotifier::toUser($order->user_id, 'orders', [
            'message' => '订单已创建',
            'orderId' => $order->id,
        ]);

        return $order;
    }
}
```

```php
// 前端组件
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Sse\SseHelper;
use Framework\View\Base\Element;

class NotificationBadge extends LiveComponent
{
    #[State(default: 0)]
    public int $count = 0;

    #[LiveAction]
    public function refresh(): void
    {
        $this->count = Notification::unreadCount();
    }

    public function render(): Element
    {
        return Element::make('span')
            ->class('notification-badge')
            ->data('live-sse', 'notifications')
            ->text((string) $this->count);
    }
}
```

### 16.5 流式 AI 对话

```php
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LiveStream;
use Framework\Component\Live\Stream\StreamBuilder;
use Framework\Http\Response\StreamResponse;
use Framework\View\Base\Element;

class ChatBot extends LiveComponent
{
    #[State]
    public string $input = '';

    #[LiveStream(format: 'ndjson')]
    public function chat(): StreamResponse
    {
        return StreamBuilder::create()
            ->thinking('分析你的问题...')
            ->each($this->generateResponse($this->input), function ($token) {
                return StreamBuilder::textChunk($token);
            })
            ->done(['timestamp' => time()])
            ->build();
    }

    private function generateResponse(string $input): \Generator
    {
        // 调用 AI API，逐 token 返回
        foreach ($this->aiService->stream($input) as $token) {
            yield $token;
        }
    }

    public function render(): Element
    {
        return Element::make('div')
            ->class('chat-bot')
            ->children([
                Element::make('div')
                    ->class('chat-messages')
                    ->attr('data-stream-target', ''),
                Element::make('input')
                    ->attr('type', 'text')
                    ->attr('data-live-model', 'input'),
                Element::make('button')
                    ->data('action', 'chat')
                    ->text('发送'),
            ]);
    }
}
```

---

## 附录：生命周期钩子

| 钩子 | 触发时机 | 典型用途 |
|------|---------|---------|
| `mount()` | 组件首次初始化 | 设置默认值、加载初始数据 |
| `hydrate()` | 状态反序列化后 | 恢复关联对象、重建缓存 |
| `onUpdate()` | 状态更新后（/live/state） | 联动计算、触发副作用 |
| `dehydrate()` | 状态序列化前 | 持久化属性、清理资源 |

## 附录：前端事件

| 事件名 | 说明 |
|--------|------|
| `y:updated` | DOM 更新完成后触发 |
| `y:locale-changed` | 语言切换后触发 |
| `y:persistent:restored` | 持久化数据恢复后触发 |
| `live:{eventName}` | 组件 emit 事件的本地广播 |
| `sse:message` | SSE 收到普通消息 |
| `l:ready` | Live 系统初始化完成 |
