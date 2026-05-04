# ymeow/framework 学习指南

> **框架定位**: Anti-MVC, route-first, component-driven PHP 框架
> **版本**: 0.1.14-dev
> **PHP 版本**: ^8.4

---

## 目录

1. [快速开始](#1-快速开始)
2. [框架架构](#2-框架架构)
3. [路由系统](#3-路由系统)
4. [数据库与 Model](#4-数据库与-model)
5. [认证系统](#5-认证系统)
6. [验证器](#6-验证器)
7. [视图组件系统](#7-视图组件系统)
8. [实时组件 (LiveComponent)](#8-实时组件-livecomponent)
9. [服务容器](#9-服务容器)
10. [事件系统](#10-事件系统)
11. [会话与 Cookie](#11-会话与-cookie)
12. [日志系统](#12-日志系统)
13. [配置管理](#13-配置管理)
14. [全局辅助函数](#14-全局辅助函数)
15. [中间件](#15-中间件)
16. [服务提供者](#16-服务提供者)

---

## 1. 快速开始

### 1.1 安装

```bash
composer require ymeow/framework
```

### 1.2 入口文件

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

$app = new \Framework\Foundation\Application(__DIR__ . '/..');
$app->run();
```

### 1.3 第一个路由

> **路由缓存说明**: 框架支持路由缓存以提升生产性能。`register_route()` 传入闭包的路由**无法被缓存**，因此在正式项目中应优先使用 Attribute 路由。`register_route()` 仅推荐用于快速原型或开发调试。

#### 方式 1: Attribute 路由（推荐）

```php
use Framework\Routing\Attribute\Route;

class HomeController
{
    #[Route(path: '/', methods: ['GET'])]
    public function index()
    {
        return 'Hello World';
    }
}
```

#### 方式 2: 全局函数注册（仅快速原型）

```php
register_route([
    'get' => '/',
    'uses' => function () {
        return 'Hello World';
    },
]);
```

---

## 2. 框架架构

### 2.1 目录结构

```
src/
├── Foundation/          # 核心基础
│   ├── Application.php  # 应用入口
│   ├── Container.php    # 服务容器
│   ├── Kernel.php       # HTTP 内核
│   ├── ServiceProvider.php  # 服务提供者基类
│   └── AppEnvironment.php   # 环境检测
├── Routing/             # 路由系统
│   ├── Router.php
│   ├── MiddlewareManager.php
│   └── Attribute/       # Attribute 路由
│       ├── Route.php
│       ├── RouteGroup.php
│       └── Middleware.php
├── Database/            # 数据库层
│   ├── Connection.php
│   ├── QueryBuilder.php
│   ├── Model.php
│   ├── Relations/       # 关联关系
│   └── Schema/          # 数据库迁移
├── Auth/                # 认证系统
│   ├── AuthManager.php
│   ├── UserProvider.php
│   └── EloquentUserProvider.php
├── Validation/          # 验证器
│   └── Validator.php
├── Http/                # HTTP 层
│   ├── Request.php
│   ├── Response.php
│   ├── Session.php
│   ├── Cookie.php
│   └── SessionServiceProvider.php
├── View/                # 视图系统
│   ├── Base/Element.php     # HTML 元素基类
│   ├── Document/Document.php  # HTML 文档构建器
│   └── Container/
├── Component/           # 组件
│   └── Live/
│       ├── LiveComponent.php        # 实时组件基类（trait 拆分）
│       ├── LiveRequestHandler.php   # Live 请求路由处理器
│       ├── LiveEventBus.php         # 跨组件事件总线
│       ├── LiveNotifier.php         # 通知系统
│       ├── ConfirmDialog.php        # 确认对话框
│       ├── LanguageSwitcherLive.php # 语言切换器示例
│       ├── Attribute/               # Live 组件 Attribute
│       │   ├── State.php
│       │   ├── Prop.php
│       │   ├── Computed.php
│       │   ├── LiveAction.php
│       │   ├── LiveListener.php
│       │   ├── LivePoll.php
│       │   ├── LiveSse.php
│       │   ├── LiveStream.php
│       │   ├── Persistent.php
│       │   ├── Session.php
│       │   ├── Cookie.php
│       │   └── Rule.php
│       ├── Concerns/                # LiveComponent Trait
│       │   ├── HasState.php         # 状态序列化/反序列化
│       │   ├── HasProperties.php    # 属性注入/反射/过滤
│       │   ├── HasActions.php       # Action 注册/调用
│       │   └── HasOperations.php    # 操作队列/UX 辅助
│       └── Persistent/              # 持久化驱动
│           ├── RedisDriver.php
│           └── ...
├── Events/              # 事件系统
│   └── Hook.php
├── Config/              # 配置管理
│   └── ConfigManager.php
├── Log/                 # 日志
│   └── LogManager.php
├── Support/             # 支持
│   └── helpers.php      # 全局辅助函数
├── Lifecycle/           # 生命周期
│   └── LifecycleManager.php
└── statics/             # 前端资源
    ├── y-live/          # Live 前端引擎
    ├── y-directive/     # 响应式指令系统
    └── y-ux/            # UX 组件库
```

### 2.2 请求处理流程

```
Request → Kernel → Router (Route Cache) → Middleware → Controller/Action → Response
                 ↓
           Auth Check
                 ↓
           LiveRequestHandler → LiveComponent Action
```

流程说明：
1. `Request` 进入 `Kernel`
2. `Kernel` 调用 `Router` 匹配路由（生产环境优先使用**路由缓存**）
3. 经过 `Middleware` 链（全局 → 路由组 → 方法）
4. 若匹配到 `LiveComponent`，触发 `Auth Check` → `LiveRequestHandler` → `LiveComponent Action`
5. 否则执行普通 `Controller/Action`，返回 `Response`

> **路由缓存**: 生产环境下框架会缓存编译后的路由表（仅缓存 Attribute 路由），避免每次请求都重新解析路由定义。`register_route()` 传入的闭包路由不会进入缓存。

---

## 3. 路由系统

### 3.1 Attribute 路由（推荐）

#### 基础路由

```php
use Framework\Routing\Attribute\Route;

class PostController
{
    #[Route(path: '/posts', methods: ['GET'])]
    public function index()
    {
        return view('posts.index');
    }

    #[Route(path: '/posts/{id}', methods: ['GET'])]
    public function show(int $id)
    {
        return view('posts.show', ['id' => $id]);
    }

    #[Route(path: '/posts', methods: ['POST'])]
    public function store()
    {
        // ...
    }
}
```

#### 多方法路由

```php
#[Route(path: '/posts', methods: ['GET', 'POST'])]
class PostController
{
    public function index() { /* GET */ }
    public function store() { /* POST */ }
}
```

#### 路由组

```php
use Framework\Routing\Attribute\RouteGroup;

#[RouteGroup(prefix: '/admin', middleware: [AdminMiddleware::class])]
class AdminController
{
    #[Route(path: '/dashboard')]
    public function dashboard() { /* /admin/dashboard */ }
}
```

### 3.2 中间件路由

```php
use Framework\Routing\Attribute\Middleware;

#[Middleware(AuthMiddleware::class)]
class UserController
{
    #[Middleware(LogMiddleware::class, priority: -10)]
    #[Middleware(ThrottleMiddleware::class, params: ['max' => 60])]
    public function show(int $id)
    {
        // ...
    }

    // 仅应用到特定方法
    #[Middleware(AuthMiddleware::class, only: ['store', 'update'])]
    public function store() { /* 需要认证 */ }
    
    public function index() { /* 公开 */ }
}
```

### 3.3 全局函数注册（仅快速原型）

> **注意**: 本方式传入闭包的路由**无法被路由缓存**，仅推荐用于开发调试、快速原型或一次性脚本。生产环境请使用 Attribute 路由。

```php
register_route([
    'get' => '/api/status',
    'uses' => function () {
        return response()->json(['status' => 'ok']);
    },
]);
```

---

## 4. 数据库与 Model

### 4.1 Model 基础

```php
namespace App\Models;

use Framework\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    
    // 时间戳
    public const UPDATED_AT = 'updated_at';
    public const CREATED_AT = 'created_at';
}
```

### 4.2 查询构造器

```php
use App\Models\User;

// 基础查询
$users = User::all();
$user = User::find(1);
$user = User::where('email', 'test@example.com')->first();

// 条件查询
$users = User::where('age', '>=', 18)
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// 模糊查询
$users = User::whereLike('name', '%john%')->get();

// 原生查询
$results = User::query()->selectRaw('*, COUNT(*) as count')
    ->groupBy('role')
    ->get();

// 插入
$id = User::query()->insert([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// 更新
User::query()->where('id', 1)->update(['name' => 'Jane']);

// 删除
User::query()->where('id', 1)->delete();
```

### 4.3 关联关系

#### BelongsTo（多对一）

```php
class Post extends Model
{
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

// 使用
$post = Post::find(1);
echo $post->author->name;
```

#### HasMany（一对多）

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
    }
}

// 使用
$user = User::find(1);
foreach ($user->posts as $post) {
    echo $post->title;
}
```

### 4.4 数据库迁移

```php
use Framework\Database\Schema\Blueprint;
use Framework\Database\Schema\Schema;

Schema::create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrentOnUpdate();
});

// 外键
Schema::table('posts', function (Blueprint $table) {
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');
});
```

---

## 5. 认证系统

### 5.1 基础认证

```php
use Framework\Auth\AuthManager;

class AuthController
{
    public function __construct(private AuthManager $auth) {}

    public function login()
    {
        if ($this->auth->attempt(['email' => $email, 'password' => $password])) {
            return redirect('/dashboard');
        }
        return back()->withError('Credentials invalid');
    }

    public function logout()
    {
        $this->auth->logout();
        return redirect('/');
    }
}
```

### 5.2 检查认证状态

```php
// 是否已登录
if ($auth->check()) {
    $user = $auth->user();
}

// 是否未登录（游客）
if ($auth->guest()) {
    // ...
}

// 获取用户 ID
$id = $auth->id();
```

### 5.3 记住我功能

```php
$auth->attempt($credentials, remember: true);
// 自动设置 remember_token cookie
```

### 5.4 一次性认证（不设置 session）

```php
// 仅用于 API 等无状态场景
if ($auth->once($credentials)) {
    $user = $auth->user();
}
```

### 5.5 用户模型接口

```php
interface Authenticatable
{
    public function getAuthIdentifier();
    public function getAuthPassword();
    public function validateCredentials($plain, $hashed);
}
```

---

## 6. 验证器

### 6.1 基础使用

```php
use Framework\Validation\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
]);

if ($validator->fails()) {
    return back()->withErrors($validator);
}
```

### 6.2 验证规则

| 规则 | 说明 |
|------|------|
| `required` | 必填 |
| `string` | 字符串类型 |
| `integer` | 整数类型 |
| `email` | 邮箱格式 |
| `url` | URL 格式 |
| `max:`n | 最大长度/值 |
| `min:`n | 最小长度/值 |
| `unique:table,column` | 数据库唯一 |
| `confirmed` | 需要 xxx_confirmation 字段 |
| `in:val1,val2` | 在枚举值中 |
| `not_in:val1,val2` | 不在枚举值中 |
| `regex:pattern` | 正则匹配 |

### 6.3 自定义错误信息

```php
$validator = Validator::make($data, $rules, [
    'name.required' => '姓名不能为空',
    'email.email' => '邮箱格式不正确',
]);
```

---

## 7. 视图组件系统

### 7.1 Element 基类

```php
use Framework\View\Base\Element;

// 基础 HTML 构建
$div = Element::make('div')
    ->id('container')
    ->class('p-4', 'bg-white', 'rounded')
    ->text('Hello World');

echo $div;
// <div id="container" class="p-4 bg-white rounded">Hello World</div>
```

### 7.2 链式调用

```php
$button = Element::make('button')
    ->class('btn', 'btn-primary')
    ->attr('type', 'submit')
    ->style('cursor: pointer')
    ->data('action', 'save')
    ->text('保存');

// 嵌套子元素
$button->child(Element::make('i')->class('icon-save'));
```

### 7.3 动态内容

```php
// 自动转义文本
Element::make('span')->text($unsafeHtml);

// XSS 过滤的 HTML
Element::make('div')->html($userContent);

// 直接输出（无过滤，慎用）
Element::make('div')->raw($trustedHtml);
```

### 7.4 国际化

```php
Element::make('button')
    ->data('intl', 'common.submit')
    ->text('提交');
// 自动从语言包翻译
```

### 7.5 Document 文档构建器

```php
use Framework\View\Document\Document;

$doc = Document::make('首页')
    ->meta('description', '网站描述')
    ->css('/assets/app.css')
    ->js('/assets/app.js')
    ->header(Element::make('nav')->text('导航'))
    ->main(Element::make('main')->text('内容'))
    ->footer(Element::make('footer')->text('底部'));

echo $doc;
```

### 7.6 WASM/Tauri 适配

```php
// 自动检测环境
$doc = Document::make('标题')
    ->main($content)
    ->render();  // Web 输出完整 HTML，WASM 只输出 <main>

// 手动指定模式
$doc->mode(Document::MODE_PARTIAL);

// Tauri JS Bridge 调用
echo $doc->toJson();
// {"title": "...", "html": "...", "mode": "partial", "assets": {...}}
```

---

## 8. 实时组件 (LiveComponent)

### 8.1 创建组件

```php
namespace App\Component;

use Framework\Component\Live\LiveComponent;

class Counter extends LiveComponent
{
    #[State]
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
    }

    public function render(): Element
    {
        return Element::make('div')
            ->class('counter')
            ->children([
                Element::make('span')
                    ->bindText('count')  // 双向绑定
                    ->text($this->count),

                // 方式 1: 新格式 data-live-action:click="increment"
                Element::make('button')
                    ->liveAction('increment')
                    ->text('+1'),

                // 方式 2: 指定事件类型（生成 data-live-action:input="search"）
                Element::make('input')
                    ->liveAction('search', 'input')
                    ->attr('type', 'text'),

                // 方式 3: 兼容旧格式（生成 data-action + data-action-event）
                Element::make('button')
                    ->liveAction('save', 'click', legacyAttrs: true)
                    ->text('保存'),
            ]);
    }
}
```

### 8.2 数据绑定

```php
// 双向绑定表单
Element::make('input')
    ->liveModel('username')  // 同步到 $this->username
    ->attr('type', 'text')
    ->placeholder('用户名');

// 事件绑定 — 新格式：data-live-action:eventType
Element::make('button')
    ->liveAction('save')  // 生成 data-live-action:click="save"
    ->text('保存');

// 事件绑定 — 指定事件类型
Element::make('button')
    ->liveAction('search', 'input')  // 生成 data-live-action:input="search"
    ->text('搜索');

// 参数传递 — 支持 JSON 或表达式
// JSON 格式（自动检测）
Element::make('button')
    ->liveParams(['id' => 123])  // data-live-action-params='{"id":123}'
    ->text('提交');

// 表达式格式（前端状态求值）
Element::make('button')
    ->liveParams("{count: count + 1, id: item.id}")  // 前端实时求值
    ->text('提交');

// 禁用条件 — data-live-disabled
Element::make('button')
    ->liveAction('save')
    ->liveDisabled('count === 0')  // 当 count === 0 时禁用
    ->text('保存');

// 分片更新
Element::make('div')
    ->liveFragment('status')  // 只更新这部分
    ->text($this->status);
```

### 8.3 响应式指令

```php
// 条件渲染
Element::make('div')
    ->bindIf('showAlert')  // 根据 JS 变量显示/隐藏
    ->text('提示消息');

// 列表循环
Element::make('ul')
    ->bindFor('item in items')
    ->children([
        Element::make('li')
            ->bindText('item.name')
            ->dataClass("{ active: item.id === selectedId }"),
    ]);

// 事件监听
Element::make('div')
    ->bindOn('click', 'handleClick')
    ->bindOn('keyup', 'handleKeyup');

// DOM 引用
Element::make('input')
    ->bindRef('myInput')  // 在 JS 中通过 ref 访问
    ->attr('type', 'text');
```

### 8.4 状态标记 — `#[State]` 与 `#[Prop]`

LiveComponent 的状态管理通过两组属性标记来控制暴露、持久化与安全校验：

| 标记 | 暴露给前端 | 前端可修改 | 参与 checksum | 用途 |
|------|-----------|-----------|--------------|------|
| `#[State]` | ✅ | ✅ | ❌ 跳过 | 组件自身的可修改状态 |
| `#[Prop]` | ✅ | ❌ | ✅ | 从父组件接收的只读属性 |
| `#[Session]` | ✅ | ❌ | ✅ | 持久化到 Session |
| `#[Cookie]` | ✅ | ❌ | ✅ | 持久化到 Cookie |

> 未标注任何属性的公开字段**不会**暴露给前端，也不参与任何序列化或校验。

#### `#[State]` — 可修改状态

标记组件的响应式状态，前端可以直接修改（如 `liveModel` 双向绑定）：

```php
use Framework\Component\Live\Attribute\State;

class Counter extends LiveComponent
{
    #[State]
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }
}
```

`#[State]` 默认 `frontendEditable: true`，跳过 checksum 校验——因为前端发来的值就是最新的，不需要和服务端保存的旧值比对。可设置 `frontendEditable: false` 禁止前端直接写入：

```php
#[State(frontendEditable: false)]
public array $internalCache = [];
```

#### `#[Prop]` — 父组件传值

从父组件接收数据，前端**不可直接修改**，参与 checksum 防篡改校验：

```php
use Framework\Component\Live\Attribute\Prop;

class UserCard extends LiveComponent
{
    #[Prop]
    public string $name;

    #[Prop]
    public int $age = 0;  // 提供默认值

    #[Prop(name: 'display_name')]  // 别名，父组件传 display_name，组件内用 $this->displayName
    public string $displayName = '';

    public function render()
    {
        return Element::make('div')
            ->class('user-card')
            ->children([
                Element::make('h3')->text($this->name),
                Element::make('p')->text("年龄：{$this->age}"),
            ]);
    }
}

// 父组件中传递 Prop
public function render()
{
    return Element::make('div')->children([
        UserCard::make([
            'name' => '张三',
            'age' => 28,
        ]),
    ]);
}
```

### 8.5 计算属性 — `#[Computed]`

缓存方法返回值，避免重复计算：

```php
use Framework\Component\Live\Attribute\Computed;

class InvoiceList extends LiveComponent
{
    public array $items = [];
    public string $currency = 'CNY';

    #[Computed]
    public function total(): float
    {
        // 仅在依赖数据变化时重算
        return array_sum(array_column($this->items, 'price'));
    }

    #[Computed(name: 'formatted_total')]
    public function formattedTotal(): string
    {
        return sprintf('¥%.2f', $this->total());
    }

    public function render()
    {
        return Element::make('div')->children([
            Element::make('p')->bindText('formatted_total'),
            Element::make('ul')->bindFor('item in items'),
        ]);
    }
}

// 视图中通过 bindText 绑定计算属性名即可
```

### 8.6 事件监听 — `#[LiveListener]`

跨组件通信：监听 `LiveEventBus` 发出的事件：

```php
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Component\Live\LiveEventBus;

class NotificationList extends LiveComponent
{
    public array $notifications = [];

    #[LiveListener('notification.sent')]
    public function onNotificationSent(array $payload): void
    {
        $this->notifications[] = $payload;

        // 使用 LiveEventBus 发出事件
        LiveEventBus::emit('notification.sent', [
            'title' => '新消息',
            'content' => '你有新的订单通知',
        ]);
    }

    // 支持优先级（数字越小越先执行）
    #[LiveListener('order.created', priority: -10)]
    public function onOrderCreated(array $data): void
    {
        // 高优先级处理
    }

    public function render()
    {
        return Element::make('div')->bindFor('notification in notifications');
    }
}
```

### 8.7 轮询 — `#[LivePoll]`

定期自动调用方法刷新数据：

```php
use Framework\Component\Live\Attribute\LivePoll;

class TaskProgress extends LiveComponent
{
    public int $progress = 0;
    public string $status = 'pending';

    // 每 3 秒轮询一次，立即执行
    #[LivePoll(interval: 3000)]
    public function checkProgress(): array
    {
        $task = Task::find($this->taskId);
        $this->progress = $task->progress;
        $this->status = $task->status;

        return [
            'progress' => $this->progress,
            'status' => $this->status,
        ];
    }

    // 条件轮询：status 不是 completed 时才继续
    #[LivePoll(interval: 1000, condition: 'status !== "completed"')]
    public function syncStatus(): array
    {
        // ...
    }

    // 不是立即执行，而是延迟 5 秒后开始
    #[LivePoll(interval: 5000, immediate: false)]
    public function refreshData(): array
    {
        return $this->repository->getLatest();
    }
}
```

### 8.8 SSE 推送 — `#[LiveSse]`

通过 Server-Sent Events 建立长连接，服务器持续推送数据：

```php
use Framework\Component\Live\Attribute\LiveSse;
use Framework\Component\Live\Sse\SseResponse;
use Framework\Component\Live\Sse\SseHub;

class NotificationStream extends LiveComponent
{
    #[LiveSse(keepAlive: 30, channels: ['notifications'])]
    public function notificationStream(): SseResponse
    {
        return SseResponse::create()
            ->event('init', ['status' => 'connected'])
            ->keepAlive(30)
            ->onInterval(function () {
                $msgs = SseHub::getMessages('notifications', $since);
                return $msgs
                    ? ['event' => 'notification', 'data' => $msgs]
                    : null;
            }, 1000);
    }
}
```

### 8.9 流式响应 — `#[LiveStream]`

适用于大模型对话、文件生成等需要逐块输出的场景：

```php
use Framework\Component\Live\Attribute\LiveStream;
use Framework\Component\Live\Stream\StreamBuilder;

class ChatStream extends LiveComponent
{
    #[LiveStream(format: 'ndjson')]
    public function chatStream(): \Framework\Component\Live\Stream\StreamResponse
    {
        return StreamBuilder::create()
            ->thinking('思考中...')
            ->each($this->generateTokens(), fn($token) => StreamBuilder::textChunk($token))
            ->done();
    }
}
```

### 8.10 状态持久化 — `#[Persistent]` / `#[Session]` / `#[Cookie]`

组件状态跨请求持久化，支持多种驱动：

```php
use Framework\Component\Live\Attribute\Persistent;
use Framework\Component\Live\Attribute\Session;
use Framework\Component\Live\Attribute\Cookie;

class SettingsForm extends LiveComponent
{
    // 持久化到后端（默认：Redis 驱动）
    #[Persistent]
    public array $config = [];

    // 持久化到 Redis 驱动，自定义 key
    #[Persistent(driver: 'redis', key: 'user_preferences')]
    public array $preferences = [];

    // 持久化到数据库驱动
    #[Persistent(driver: 'database')]
    public ?int $selectedProjectId = null;

    // 绑定到 PHP Session（每次请求自动恢复）
    #[Session]
    public string $tempToken = '';

    // 绑定到 Cookie（前端可读）
    #[Cookie(key: 'theme')]
    public string $theme = 'light';
}
```

**支持的多驱动列表：**

| 驱动 | 说明 | 适用场景 |
|------|------|---------|
| `redis` | Redis 缓存（默认） | 高性能，进程间共享 |
| `database` | 数据库存储 | 需要持久保存，跨服务 |
| `local_storage` | 浏览器 LocalStorage | 前端偏好设置 |
| `cookie` | Cookie | 小数据，前端可读 |
| `session` | PHP Session | 临时会话数据 |

### 8.11 验证规则 — `#[Rule]`

为组件属性添加输入验证：

```php
use Framework\Component\Live\Attribute\Rule;

class RegisterForm extends LiveComponent
{
    #[Rule('required|string|min:2|max:50')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:8|confirmed')]
    public string $password = '';

    #[Rule('required|same:password')]
    public string $passwordConfirmation = '';

    public function submit()
    {
        $this->validate();  // 触发所有 #[Rule] 验证
        // 验证通过，执行业务逻辑
    }

    public function render()
    {
        return Element::make('form')
            ->liveAction('submit')
            ->children([
                Element::make('input')->liveModel('name'),
                Element::make('input')->liveModel('email')->attr('type', 'email'),
                Element::make('input')->liveModel('password')->attr('type', 'password'),
                Element::make('input')->liveModel('passwordConfirmation')->attr('type', 'password'),
                Element::make('button')->text('注册'),
            ]);
    }
}
```

### 8.12 子组件嵌套

组件之间可以自由组合嵌套，通过 `::make()` 传入 Prop：

```php
class ParentComponent extends LiveComponent
{
    #[Prop]
    public array $users = [];

    public function render()
    {
        return Element::make('div')->children([
            Element::make('h2')->text('用户列表'),
            // 为每个用户渲染子组件
            ...array_map(
                fn($user) => UserCard::make(['name' => $user['name'], 'age' => $user['age']]),
                $this->users
            ),
        ]);
    }
}
```

### 8.13 通知系统 — `LiveNotifier`

```php
use Framework\Component\Live\LiveNotifier;

class NotifyDemo extends LiveComponent
{
    public function sendNotification()
    {
        LiveNotifier::success('操作成功完成！');
        LiveNotifier::error('出错了，请重试。');
        LiveNotifier::warning('请注意：数据将在 24 小时后过期。');
        LiveNotifier::info('你有 3 条未读消息。');
    }
}
```

### 8.14 确认对话框 — `ConfirmDialog`

```php
use Framework\Component\Live\ConfirmDialog;

class DeleteAction extends LiveComponent
{
    public function delete()
    {
        ConfirmDialog::confirm(
            title: '确认删除',
            message: '确定要删除这条记录吗？此操作不可恢复。',
            onConfirm: function () {
                // 用户确认后执行
                $this->record->delete();
                LiveNotifier::success('已删除');
            },
            onCancel: function () {
                LiveNotifier::info('已取消');
            },
            confirmText: '删除',
            cancelText: '取消',
            variant: 'danger',  // primary | danger | warning
        );
    }
}
```

### 8.15 Live 架构 — `LiveRequestHandler`

Live 组件的请求处理由 `LiveRequestHandler` 统一调度，包含 4 个路由端点：

| 端点 | 方法 | 用途 |
|------|------|------|
| `/live/update` | `POST` | 常规 Action 调用，返回 JSON 响应 |
| `/live/stream` | `POST` | 流式 Action，返回 NDJSON/SSE 流 |
| `/live/navigate` | `POST` | SPA 导航，返回目标页面 HTML 片段 |
| `/live/intl` | `POST` | 国际化翻译查询 |

**前端指令格式**：

```html
<!-- 新格式：data-live-action:eventType -->
<button data-live-action:click="save">保存</button>
<input data-live-action:input="search" type="text">

<!-- 参数支持 JSON 或表达式 -->
<button data-live-action-params="{count: count + 1}">提交</button>

<!-- 禁用条件 -->
<button data-live-disabled="count === 0">提交</button>

<!-- 兼容旧格式 -->
<button data-action="save" data-action-event="click">保存</button>
```

**LiveComponent Trait 拆分**：

| Trait | 职责 |
|-------|------|
| `HasState` | 状态序列化/反序列化、checksum 校验、签名 |
| `HasProperties` | 属性注入、反射过滤、前端数据填充 |
| `HasActions` | Action 注册、调用、参数类型转换、事件监听 |
| `HasOperations` | 操作队列、redirect/toast/modal/loading 等 |

---

*文档版本: 0.1.14-dev*
*最后更新: 2024*
