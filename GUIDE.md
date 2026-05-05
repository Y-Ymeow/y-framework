# ymeow/framework 学习指南

> **框架定位**: Anti-MVC, route-first, component-driven PHP 框架
> **版本**: 0.2.0-dev
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
9. [事件系统](#9-事件系统)
10. [服务容器与提供者](#10-服务容器与提供者)
11. [会话与 Cookie](#11-会话与-cookie)
12. [日志系统](#12-日志系统)
13. [配置管理](#13-配置管理)
14. [Module 系统](#14-module-系统)
15. [全局辅助函数](#15-全局辅助函数)
16. [中间件](#16-中间件)

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
├── Foundation/              # 核心基础
│   ├── Application.php     # 应用入口，Provider 引导，Module 管理
│   ├── Container.php       # 服务容器（DI）
│   ├── Kernel.php          # HTTP 内核，请求/响应调度
│   ├── ServiceProvider.php # 服务提供者基类
│   └── AppEnvironment.php  # 环境检测（WASM/Tauri/CLI）
├── Module/                  # 功能模块（自包含的完整解决方案）
│   ├── ModuleInterface.php  # 模块契约
│   ├── BaseModule.php      # 模块基类
│   ├── ModuleManager.php   # 模块管理器（注册/引导/依赖）
│   ├── ModuleServiceProvider.php
│   ├── User/               # 用户模块：Model + 迁移 + Gate + ServiceProvider
│   ├── Notification/      # 通知模块：Notification + SSE 推送
│   └── Mail/              # 邮件模块：MailManager + 多驱动（smtp/sendmail/log/array）
├── Routing/                # 路由系统
│   ├── Router.php         # 路由调度（支持路由缓存）
│   ├── Route.php          # 路由值对象
│   ├── RouteCollection.php # 路由集合（索引化加速匹配）
│   ├── MiddlewareManager.php
│   ├── Attribute/         # Attribute 路由
│   │   ├── Route.php
│   │   ├── RouteGroup.php
│   │   └── Middleware.php
│   ├── CssRoute.php       # Tailwind CSS 生成路由（/_css）
│   └── SystemRoute.php    # 系统路由（/_css, /_js, /live/*）
├── Database/               # 数据库层
│   ├── Connection.php
│   ├── QueryBuilder.php
│   ├── Model.php
│   ├── Relations/
│   └── Schema/
├── Auth/                   # 认证系统
│   ├── AuthManager.php
│   ├── Gate.php          # 权限定义
│   ├── User.php          # 用户模型基类
│   └── EloquentUserProvider.php
├── Validation/            # 验证器
│   └── Validator.php
├── Http/                   # HTTP 层
│   ├── Request.php
│   ├── Response/
│   │   ├── Response.php
│   │   ├── StreamedResponse.php
│   │   └── RedirectResponse.php
│   ├── Session.php
│   └── Cookie.php
├── View/                   # 视图系统
│   ├── Base/Element.php   # HTML 元素基类（精简后 ~275 行）
│   ├── Element/           # Element 子类（Container/Text/Image/Link/Listing/Table/Form）
│   ├── Concerns/          # Element Trait
│   │   ├── HasTailwindSpacing.php     # p/px/py/m/mx/gap/spaceY/spaceX
│   │   ├── HasTailwindLayout.php      # flex/grid/itemsCenter/justifyBetween/wFull/overflow
│   │   ├── HasTailwindAppearance.php  # rounded/shadow/bg/border/opacity
│   │   ├── HasTailwindTypography.php  # fontBold/textSm/textGray/truncate/uppercase
│   │   ├── HasLiveDirectives.php     # liveModel/liveAction/liveParams/liveBind/liveFragment
│   │   ├── HasBindDirectives.php      # bindText/bindModel/bindShow/bindFor/bindOn/dataClass
│   │   └── HasVisibility.php         # visible/hidden/cloak/state
│   ├── Document/
│   │   ├── Document.php      # HTML 文档构建器（builder 模式）
│   │   ├── DocumentConfig.php # 全局文档配置（title/lang/meta/injections）
│   │   ├── AssetRegistry.php  # 资源注册（CSS/JS 文件 + 片段收集）
│   │   └── CssCollector.php  # CSS 片段收集器（通过 /_css 路由统一输出）
│   ├── Fragment.php
│   └── FragmentRegistry.php
├── Component/              # 组件
│   └── Live/
│       ├── LiveComponent.php      # 实时组件基类
│       ├── LiveRequestHandler.php # Live 请求路由处理器
│       ├── LiveResponse.php       # Live 操作指令（redirect/toast/modal 等）
│       ├── LiveEventBus.php       # 跨组件事件总线
│       ├── LiveNotifier.php       # 通知系统（success/error/warning/info）
│       ├── ConfirmDialog.php      # 确认对话框
│       ├── Sse/                   # SSE 推送
│       ├── Stream/                # 流式响应
│       ├── Persistent/             # 持久化驱动（Redis/database）
│       ├── Attribute/             # Live 组件 Attribute
│       │   ├── State.php, Prop.php, Computed.php
│       │   ├── LiveAction.php, LiveListener.php
│       │   ├── LivePoll.php, LiveSse.php, LiveStream.php
│       │   ├── Persistent.php, Session.php, Cookie.php
│       │   └── Rule.php
│       └── Concerns/             # LiveComponent Trait
│           ├── HasState.php      # 状态序列化/checksum/签名
│           ├── HasProperties.php  # 属性注入/反射/过滤
│           ├── HasActions.php    # Action 注册/调用/参数转换
│           └── HasOperations.php  # 操作队列/UX 辅助
├── Events/                 # 事件系统（重构后）
│   ├── Event.php           # 事件基类（name/payload/timestamp/propagation）
│   ├── EventDispatcherInterface.php  # on/off/dispatch/emit/filter
│   ├── EventSubscriberInterface.php  # getSubscribedEvents()
│   ├── Hook.php           # 事件总线实现（通配符/订阅器/lazy listener）
│   ├── RequestEvent.php    # 类型化事件：Request/Response/Boot/LiveAction
│   ├── ResponseEvent.php
│   ├── BootEvent.php
│   ├── LiveActionEvent.php
│   └── Attribute/Listen.php  # 事件监听属性（HookListener/HookFilter 别名）
├── Config/                 # 配置管理
│   └── ConfigManager.php   # load/cache/set/get/merge/validate
├── Log/                    # 日志
│   └── LogManager.php
├── Intl/                   # 国际化
│   └── Translator.php
├── Lifecycle/              # 生命周期
│   ├── LifecycleManager.php  # 引导/Collector 管理/属性扫描
│   └── AttributeScanner.php   # 自动扫描 #[Listen] 等属性
├── Scheduling/              # 任务调度
│   └── Scheduler.php
├── Support/                # 支持工具
│   ├── helpers.php          # 全局辅助函数
│   └── Paths.php            # 路径管理器
├── Console/                 # 命令行
│   ├── Kernel.php
│   └── Commands/
│       ├── MigrateCommand.php
│       ├── ModuleMigrateCommand.php  # php y-cli module:migrate
│       └── MakeComponentCommand.php
└── statics/                # 前端资源
    ├── y-live/             # Live 前端引擎
    ├── y-directive/        # 响应式指令系统
    └── y-ux/               # UX 组件库
```

### 2.2 请求处理流程

```
Request → Kernel → Router（路由缓存）→ Middleware → Controller/Action → Response
                 ↓
           BootEvent (app.booting / app.booted)
                 ↓
           RequestEvent (request.received)
                 ↓
           Hook::emit('response.created') → Hook::filter('response.sending')
```

流程说明：
1. `Request` 进入 `Kernel`
2. `Kernel` 调用 `Router` 匹配路由（生产环境优先使用**路由缓存**）
3. 经过 `Middleware` 链（全局 → 路由组 → 方法）
4. 若匹配到 `LiveComponent`，触发 `LiveRequestHandler` → `LiveComponent Action`
5. 否则执行普通 `Controller/Action`，返回 `Response`
6. 通过事件系统分发 `response.created` / `response.sending` 供插件扩展

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

#### 路由参数约束

```php
#[Route(path: '/posts/{id}', methods: ['GET'], where: ['id' => '[0-9]+'])]
public function show(int $id) { /* ... */ }
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
    public function show(int $id) { /* ... */ }

    // 仅应用到特定方法
    #[Middleware(AuthMiddleware::class, only: ['store', 'update'])]
    public function store() { /* 需要认证 */ }

    public function index() { /* 公开 */ }
}
```

### 3.3 全局函数注册（仅快速原型）

> **注意**: 传入闭包的路由**无法被路由缓存**，仅推荐用于开发调试。

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

// OR 条件
$users = User::where('status', 'active')
    ->orWhere('role', 'admin')
    ->get();

// IN 查询
$users = User::whereIn('id', [1, 2, 3])->get();
$users = User::whereNotIn('id', [4, 5])->get();

// NULL 查询
$users = User::whereNull('deleted_at')->get();
$users = User::whereNotNull('verified_at')->get();

// BETWEEN 查询
$users = User::whereBetween('age', [18, 65])->get();
$users = User::whereNotBetween('price', [100, 200])->get();

// 列比较
$posts = Post::whereColumn('updated_at', '>', 'created_at')->get();

// EXISTS 查询
$users = User::whereExists(
    DB::table('orders')->whereColumn('orders.user_id', 'users.id')
)->get();

// 日期查询
$users = User::whereDate('created_at', '>=', '2024-01-01')->get();
$users = User::whereYear('created_at', '2024')->get();
$users = User::whereMonth('created_at', '12')->get();
$users = User::whereDay('created_at', '25')->get();

// 原生 SQL
$users = User::whereRaw('age > ? AND status = ?', [18, 'active'])->get();

// 嵌套条件
$users = User::where(function ($query) {
        $query->where('status', 'active')
              ->where('verified', true);
    })
    ->orWhere('role', 'admin')
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

// 聚合
$count = User::count();
$sum = User::sum('amount');
$avg = User::avg('price');
$max = User::max('age');
$min = User::min('age');

// 自增/自减
User::query()->where('id', 1)->increment('views', 5);
User::query()->where('id', 1)->decrement('stock', 2);
```

### 4.3 Where 表达式架构

框架的查询构造器使用**接口驱动**的 Where 表达式系统，每种条件类型都是独立类：

```php
use Framework\Database\Query\WhereExpressions\WhereExpressionInterface;

// 内部架构（用户通常无需直接操作）
interface WhereExpressionInterface {
    public function getBoolean(): string;
    public function getType(): string;
}

// 具体表达式类
BasicWhereExpression      // column, operator, value
InWhereExpression         // column, values, not flag
NullWhereExpression       // column, not flag
BetweenWhereExpression    // column, min, max, not flag
ColumnWhereExpression     // column1, operator, column2
DateWhereExpression       // column, operator, value
DatePartWhereExpression   // part, column, operator, value
ExistsWhereExpression     // query, not flag
NestedWhereExpression     // query
RawWhereExpression        // sql, bindings
```

这种设计避免了传统单一类多参数构造的问题，每种表达式只包含它需要的字段，类型安全且语义清晰。

### 4.4 关联关系

```php
class Post extends Model
{
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id', 'id');
    }
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
class AuthController
{
    public function login()
    {
        if (auth()->attempt(['email' => $email, 'password' => $password])) {
            return redirect('/dashboard');
        }
        return back()->withError('Credentials invalid');
    }

    public function logout()
    {
        auth()->logout();
        return redirect('/');
    }
}
```

### 5.2 检查认证状态

```php
if (auth()->check()) {
    $user = auth()->user();
}

if (auth()->guest()) {
    // 游客
}

$id = auth()->id();
```

### 5.3 Gate 权限

```php
// 定义权限
gate()->define('post.edit', function ($user, $post) {
    return $user->id === $post->user_id;
});

// 使用
if (gate()->allows('post.edit', $post)) {
    // 可以编辑
}
```

### 5.4 记住我 / 一次性认证

```php
// 记住我
auth()->attempt($credentials, remember: true);

// 无状态（API）
auth()->once($credentials);
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
| `max:n` | 最大长度/值 |
| `min:n` | 最小长度/值 |
| `unique:table,column` | 数据库唯一 |
| `confirmed` | 需要 xxx_confirmation 字段 |
| `in:val1,val2` | 在枚举值中 |
| `regex:pattern` | 正则匹配 |

---

## 7. 视图组件系统

### 7.1 Element 基类

Element 是框架的最小构建单位，所有 UX 组件都基于它构建。

```php
use Framework\View\Element;

// 基础 HTML 构建
$div = Element::make('div')
    ->id('container')
    ->class('p-4', 'bg-white', 'rounded')
    ->text('Hello World');

echo $div;
// <div id="container" class="p-4 bg-white rounded">Hello World</div>
```

### 7.2 Element 子类快捷方法

```php
use Framework\View\Element\Container;
use Framework\View\Element\Text;
use Framework\View\Element\Image;
use Framework\View\Element\Link;
use Framework\View\Element\Listing;
use Framework\View\Element\Table;

// Container 语义化标签
Container::section()
    ->class('p-4')
    ->children(Text::h1('Welcome'));

// Text 快捷标签
Text::h2('标题');
Text::p('段落内容');
Text::strong('粗体');
Text::code('代码');

// Image
Image::make('/img/logo.png')
    ->alt('Logo')
    ->objectCover();

// Link
Link::make('/about')
    ->blank()
    ->text('关于我们');

// Table
Table::make()
    ->headers(['ID', 'Name', 'Email'])
    ->rows([[1, 'John', 'john@example.com']])
    ->striped()
    ->hoverable();
```

### 7.3 Element 能力分层

Element 的功能分四层：

| 层级 | 功能 | 方法示例 |
|------|------|---------|
| **基础** | HTML 构建 | `make()`, `id()`, `class()`, `attr()`, `text()`, `html()`, `child()` |
| **LiveComponent** | 实时绑定 | `liveModel()`, `liveAction()`, `liveParams()`, `liveBind()`, `liveFragment()` |
| **响应式指令** | 前端引擎 | `bindText()`, `bindModel()`, `bindShow()`, `bindIf()`, `bindFor()`, `bindOn()` |
| **Tailwind** | CSS 快捷 | `p()`, `flex()`, `rounded()`, `textSm()`, `shadow()` |

### 7.4 LiveComponent 集成（第二层）

```php
// 双向数据绑定
Element::make('input')
    ->liveModel('username')  // 绑定到 LiveComponent 的 $this->username
    ->attr('type', 'text');

// 触发后端 Action
Element::make('button')
    ->liveAction('save')     // 点击调用 LiveComponent 的 save() 方法
    ->liveParams(['id' => 123])  // 传递参数
    ->liveDisabled('count === 0') // 禁用条件
    ->text('保存');
```

### 7.5 响应式指令（第三层）

这些属性由前端 `y-directive` 引擎解析：

```php
// 条件渲染
Element::make('div')
    ->bindShow('isVisible')
    ->text('可见内容');

// 列表循环
Element::make('ul')
    ->bindFor('item in items')
    ->children(
        Element::make('li')
            ->bindText('item.name')
            ->dataClass("{ active: item.id === selectedId }")
    );

// 事件监听
Element::make('button')
    ->bindOn('click', 'handleClick');

// 动态属性
Element::make('img')
    ->bindAttr('src', 'user.avatar');
```

### 7.6 Tailwind 快捷方法（第四层）

```php
// 间距
->p(4)->px(6)->py(2)->m(4)->mx('auto')->gap(4)->spaceY(4)

// 布局
->flex('col')->grid(3)->itemsCenter()->justifyBetween()->wFull()->overflow('hidden')

// 外观
->rounded('lg')->shadow('md')->bg('blue-500')->border()->opacity(50)

// 排版
->fontBold()->textSm()->textGray('500')->textCenter()->truncate()->uppercase()
```

### 7.7 Document 文档构建器

```php
use Framework\View\Document\Document;

$doc = Document::make('首页')
    ->meta('description', '网站描述')
    ->ux()                   // 加载 UX 组件库 CSS/JS
    ->css('/assets/app.css')
    ->main(
        Element::make('div')
            ->class('p-4')
            ->text('Hello')
    );

echo $doc;
```

Document 使用 `DocumentConfig` 存储全局配置（title/lang/meta/injections），支持静态方法设置默认值：
```php
Document::setTitle('我的应用');
Document::setLang('en');
Document::addMeta('keywords', 'framework, php');
Document::injectStatic('head', '<link rel="canonical" href="...">');
```

### 7.8 CSS 资源管理

框架通过 `/_css` 路由统一输出 CSS：
- **Tailwind CSS**：扫描项目中所有 `->class()` 调用，按需生成
- **CSS 片段**：通过 `AssetRegistry::inlineStyle($id, $css)` 注册，通过 `CssCollector` 收集，通过 `/_css` 统一输出

```php
// 注册 CSS 片段
AssetRegistry::getInstance()->inlineStyle('ux:toast', '.toast { ... }');

// 在 Document 中自动通过 /_css 路由输出
$doc->core(); // 输出 <link rel="stylesheet" href="/_css">
```

### 7.9 WASM/Tauri 适配

```php
// 自动检测环境：Web 输出完整 HTML，WASM 只输出 main 部分
$doc = Document::make('标题')->main($content)->render();

// 手动指定模式
$doc->mode(Document::MODE_PARTIAL);  // 只输出 main 内容
$doc->mode(Document::MODE_FRAGMENT); // 只输出传入的片段

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
                    ->bindText('count')
                    ->text((string)$this->count),

                Element::make('button')
                    ->liveAction('increment')
                    ->text('+1'),
            ]);
    }
}
```

### 8.2 数据绑定

```php
// 双向绑定（liveModel 同时设置 data-live-model 和 data-model）
Element::make('input')
    ->liveModel('username')
    ->attr('type', 'text')
    ->placeholder('用户名');

// 触发 Action（支持事件类型）
Element::make('button')->liveAction('save');            // data-live-action:click="save"
Element::make('input')->liveAction('search', 'input');  // data-live-action:input="search"

// 传递参数
Element::make('button')
    ->liveParams(['id' => 123])  // JSON 格式
    ->liveParams("{count: count + 1}")  // 表达式格式（前端实时求值）
    ->text('提交');

// 禁用条件
Element::make('button')
    ->liveAction('save')
    ->liveDisabled('count === 0');

// 分片更新（只刷新该区域）
Element::make('div')
    ->liveFragment('status')
    ->text($this->status);
```

### 8.3 响应式指令

```php
// 条件渲染
Element::make('div')->bindIf('showAlert')->text('提示');

// 列表循环
Element::make('ul')
    ->bindFor('item in items')
    ->children(
        Element::make('li')
            ->bindText('item.name')
            ->dataClass("{ active: item.id === selectedId }")
    );

// 事件监听
Element::make('button')
    ->bindOn('click', 'handleClick')
    ->bindOn('keyup', 'handleKeyup');

// DOM 引用
Element::make('input')->bindRef('myInput')->attr('type', 'text');
```

### 8.4 状态标记

| 标记 | 暴露给前端 | 前端可修改 | 参与 checksum | 用途 |
|------|-----------|-----------|--------------|------|
| `#[State]` | ✅ | ✅ | ❌ 跳过 | 组件自身的可修改状态 |
| `#[Prop]` | ✅ | ❌ | ✅ | 从父组件接收的只读属性 |
| `#[Session]` | ✅ | ❌ | ✅ | 持久化到 Session |
| `#[Cookie]` | ✅ | ❌ | ✅ | 持久化到 Cookie |

### 8.5 计算属性 — `#[Computed]`

```php
#[Computed]
public function total(): float
{
    return array_sum(array_column($this->items, 'price'));
}
```

### 8.6 事件监听 — `#[LiveListener]`

```php
#[LiveListener('notification.sent')]
public function onNotificationSent(array $payload): void
{
    $this->notifications[] = $payload;
}

// 发出事件
LiveEventBus::emit('notification.sent', ['title' => '新消息']);
```

### 8.7 轮询 — `#[LivePoll]`

```php
// 每 3 秒轮询
#[LivePoll(interval: 3000)]
public function checkProgress(): array { /* ... */ }

// 条件轮询
#[LivePoll(interval: 1000, condition: 'status !== "completed"')]

// 延迟启动
#[LivePoll(interval: 5000, immediate: false)]
```

### 8.8 SSE 推送 — `#[LiveSse]`

```php
#[LiveSse(keepAlive: 30, channels: ['notifications'])]
public function notificationStream(): SseResponse { /* ... */ }
```

### 8.9 流式响应 — `#[LiveStream]`

```php
#[LiveStream(format: 'ndjson')]
public function chatStream(): \Framework\Component\Live\Stream\StreamResponse
{
    return StreamBuilder::create()
        ->thinking('思考中...')
        ->each($this->generateTokens(), fn($token) => StreamBuilder::textChunk($token))
        ->done();
}
```

### 8.10 状态持久化

```php
#[Persistent]                        // Redis 驱动（默认）
public array $config = [];

#[Persistent(driver: 'redis', key: 'user_preferences')]
public array $preferences = [];

#[Persistent(driver: 'database')]
public ?int $selectedProjectId = null;

#[Session]
public string $tempToken = '';

#[Cookie(key: 'theme')]
public string $theme = 'light';
```

### 8.11 验证规则 — `#[Rule]`

```php
class RegisterForm extends LiveComponent
{
    #[Rule('required|string|min:2|max:50')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:8|confirmed')]
    public string $password = '';

    public function submit()
    {
        $this->validate();  // 触发所有 #[Rule] 验证
    }
}
```

### 8.12 通知系统 — `LiveNotifier`

```php
LiveNotifier::success('操作成功！');
LiveNotifier::error('出错了，请重试。');
LiveNotifier::warning('数据将在 24 小时后过期。');
LiveNotifier::info('你有 3 条未读消息。');
```

### 8.13 确认对话框 — `ConfirmDialog`

```php
ConfirmDialog::confirm(
    title: '确认删除',
    message: '确定要删除这条记录吗？',
    onConfirm: function () {
        $this->record->delete();
        LiveNotifier::success('已删除');
    },
    onCancel: function () {
        LiveNotifier::info('已取消');
    },
    variant: 'danger',
);
```

### 8.14 Live 架构

Live 组件的请求处理由 `LiveRequestHandler` 统一调度，包含 4 个路由端点：

| 端点 | 方法 | 用途 |
|------|------|------|
| `/live/update` | `POST` | 常规 Action 调用，返回 JSON 响应 |
| `/live/stream` | `POST` | 流式 Action，返回 NDJSON/SSE 流 |
| `/live/navigate` | `POST` | SPA 导航，返回目标页面 HTML 片段 |
| `/live/intl` | `POST` | 国际化翻译查询 |

---

## 9. 事件系统

### 9.1 设计理念

框架的事件系统是**真正的 EventDispatcher 模式**，不是 WordPress 风格的字符串 hook。有类型化事件对象、订阅器模式、通配符支持。

### 9.2 核心接口

```php
// 监听
Hook::getInstance()->on('user.registered', function (Event $event) {
    $userId = $event->get('user_id');
}, 10);

// 触发（无返回值）
Hook::getInstance()->emit('user.registered', [$userId, 'email@example.com']);

// 过滤（链式修改值）
$response = Hook::getInstance()->filter('response.sending', $response, [$request]);

// 分发类型化事件对象
$event = new \Framework\Events\RequestEvent($request);
Hook::getInstance()->dispatch($event); // 返回 Event，可阻止传播
```

### 9.3 全局辅助函数

```php
// 分发类型化事件（推荐）
event(new RequestEvent($request));

// 触发字符串事件
emit('app.booted');

// 注册监听器
listen('app.booted', function () { /* ... */ }, 10);

// 链式过滤
filter('response.sending', $response, [$request]);
```

### 9.4 通配符监听

```php
// 监听所有 request.* 事件
Hook::getInstance()->on('request.*', function () {
    // 会匹配 request.received, request.processed 等
});

// 监听所有 live.* 事件
Hook::getInstance()->on('live.*', function (Event $event) {
    // 所有 live.action, live.render 等
});
```

### 9.5 订阅器模式

实现 `EventSubscriberInterface`，批量注册相关监听器：

```php
class AppSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'app.booted' => 'onBooted',
            'request.received' => ['onRequest', 10],    // [方法名, 优先级]
            'response.sending' => ['onResponse', -10],
        ];
    }

    public function onBooted(): void { /* ... */ }
    public function onRequest(): void { /* ... */ }
    public function onResponse(): void { /* ... */ }
}

// 注册
Hook::getInstance()->addSubscriber(new AppSubscriber());
```

### 9.6 属性监听

在类方法上使用 `#[Listen]` 属性自动注册：

```php
use Framework\Events\Attribute\Listen;

class DashboardListener
{
    #[Listen('app.booted')]
    public function onBooted(): void
    {
        // 应用启动后执行
    }

    #[Listen('request.received', priority: 100)]
    public function onRequest(): void
    {
        // 高优先级，在其他监听器之前执行
    }
}
```

### 9.7 类型化事件

框架内置了类型化事件类：

```php
// RequestEvent — 请求到达时
$event = new RequestEvent($request);
$request = $event->getRequest();

// ResponseEvent — 响应生成后，可修改
$event = new ResponseEvent('response.created', $response, $request);
$event->setResponse($newResponse);

// BootEvent — 应用启动阶段
event(new BootEvent('app.booting'));
event(new BootEvent('app.booted'));
```

### 9.8 事件传播控制

```php
$event->stopPropagation();  // 阻止后续监听器执行
if ($event->isPropagationStopped()) { /* ... */ }
```

### 9.9 框架内置事件

| 事件 | 时机 |
|------|------|
| `app.booting` | 应用启动前 |
| `app.booted` | 应用启动完成 |
| `request.received` | 收到请求 |
| `response.created` | 响应生成后 |
| `response.sending` | 响应发送前（可过滤修改） |
| `response.sent` | 响应发送后 |
| `live.action.completed` | Live Action 执行完成 |

---

## 10. 服务容器与提供者

### 10.1 服务容器

```php
// 绑定
app()->bind(Connection::class, fn () => new Connection($config));

// 单例
app()->singleton(MailManager::class, fn () => new MailManager());

// 实例
app()->instance('cache', new RedisCache());

// 别名
app()->alias(MailManager::class, 'mail');

// 解析
$connection = app()->make(Connection::class);
$cache = app('cache');
```

### 10.2 服务提供者

```php
use Framework\Foundation\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailManager::class, function () {
            return new MailManager(config('mail', []));
        });
        $this->app->alias(MailManager::class, 'mail');
    }

    public function boot(): void
    {
        // 应用启动后执行
    }
}
```

### 10.3 ModuleServiceProvider

Module 专用的 ServiceProvider，自动注入 Module 实例：

```php
use Framework\Module\ModuleServiceProvider;

class UserServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthManager::class, function () {
            return new AuthManager($this->app->make(Session::class));
        });
    }

    public function modulePath(string $path = ''): string
    {
        // 获取 Module 目录下的文件
        return $this->module->getPath() . '/' . ltrim($path, '/');
    }
}
```

---

## 11. 会话与 Cookie

### 11.1 Session

```php
$value = session()->get('key');
session()->set('key', 'value');
session()->has('key');
session()->forget('key');
session()->flush();  // 清空所有

// 闪现数据（下一个请求后自动删除）
session()->flash('success', '保存成功！');
$message = session()->get('success');
```

### 11.2 Cookie

```php
// 创建 Cookie
cookie('name', 'value', 60);  // 60 分钟

// 附加到响应
return response()->withCookie(cookie('remember', 'token', 43200));
```

---

## 12. 日志系统

```php
logger()->info('用户登录', ['user_id' => 1]);
logger()->error('操作失败', ['error' => $e->getMessage()]);
logger()->debug('SQL', ['query' => $sql]);

// 指定 channel
logger('mail')->info('邮件已发送');
```

---

## 13. 配置管理

### 13.1 获取和设置

```php
// 获取（支持点号分隔）
$debug = config('app.debug', false);
$dbHost = config('database.connections.mysql.host');

// 设置（持久化到 runtime.php）
config()->set('app.timezone', 'Asia/Shanghai');

// 运行时获取（不持久化）
$value = ConfigManager::get('key', 'default');
```

### 13.2 合并配置

```php
// 递归合并两个数组
$merged = ConfigManager::merge(['a' => 1, 'b' => 2], ['b' => 3, 'c' => 4]);
// 结果: ['a' => 1, 'b' => 3, 'c' => 4]
```

### 13.3 验证配置

```php
$errors = ConfigManager::validate([
    'app.name' => ['required' => true, 'type' => 'string'],
    'app.debug' => ['type' => 'bool'],
    'database.connections.mysql.host' => ['required' => true],
]);

if (!empty($errors)) {
    throw new \RuntimeException('配置错误: ' . implode(', ', $errors));
}
```

---

## 14. Module 系统

### 14.1 什么是 Module

Module 是**自包含的功能包**，类似 Django 的 app 或 Laravel 的 package。每个 Module 包含：
- Model（数据模型）
- Migration（数据库迁移）
- ServiceProvider（服务注册）
- Config（默认配置）
- Manager（业务管理器）

### 14.2 框架内置 Module

框架内置三个核心 Module：

| Module | 说明 | 依赖 |
|--------|------|------|
| `User` | 用户认证、Gate 权限 | - |
| `Notification` | 数据库通知 + SSE 推送 | user |
| `Mail` | 邮件发送（多驱动） | - |

### 14.3 注册和引导

Module 在 `Application::bootstrapProviders()` 中自动注册：

```php
// config/app.php
'app.modules' => [
    \Framework\Module\User\UserModule::class,
    \Framework\Module\Notification\NotificationModule::class,
    \Framework\Module\Mail\MailModule::class,
],
```

### 14.4 Module 迁移

Module 的迁移**不自动执行**，需要手动运行命令：

```bash
# 运行所有已注册模块的迁移
php y-cli module:migrate

# 只运行指定模块
php y-cli module:migrate user
php y-cli module:migrate notification

# 回滚
php y-cli module:migrate user --rollback
php y-cli module:migrate notification -r

# 先回滚再重新运行
php y-cli module:migrate user --fresh

# 列出所有模块及其状态
php y-cli module:migrate --list
```

### 14.5 创建自定义 Module

```php
// 1. 定义 Module 类
namespace App\Modules\Blog;

use Framework\Module\BaseModule;

class BlogModule extends BaseModule
{
    protected string $name = 'blog';
    protected string $path = __DIR__;
    protected ?string $serviceProvider = BlogServiceProvider::class;
    protected ?string $configFile = __DIR__ . '/config.php';
    protected ?string $migrationsPath = __DIR__ . '/migrations';
    protected array $dependencies = ['user'];  // 依赖 user 模块
}
```

### 14.6 Module 辅助函数

```php
// 获取 Module 管理器
modules();  // 返回 ModuleManager 实例

// 获取指定 Module
module('notification');  // 返回 NotificationModule 实例

// 发送通知
notify(1, 'order', '新订单', '您有新订单');  // 自动 SSE 推送

// 发送邮件
mailer()->to('user@example.com')->send($mailable);
```

### 14.7 User Module 详情

```
Module/User/
  UserModule.php              # 模块定义
  UserServiceProvider.php     # 注册 AuthManager + Gate
  User.php                   # 用户模型
  config.php                 # 密码策略/记住我/邮箱验证配置
  migrations/
    0001_create_users_table.php
    0002_create_password_resets_table.php
```

### 14.8 Notification Module 详情

```
Module/Notification/
  NotificationModule.php
  NotificationServiceProvider.php
  Notification.php             # Model: send/unread/markRead/broadcast
  NotificationManager.php     # 通知管理器
  config.php
  migrations/
    0001_create_notifications_table.php
```

发送通知并自动 SSE 推送：
```php
notify(1, 'order', '新订单', '您有新订单 #12345');
```

### 14.9 Mail Module 详情

```
Module/Mail/
  MailModule.php
  MailServiceProvider.php
  MailManager.php             # 驱动解析/发送
  MailDriverInterface.php
  Mailable.php                # 邮件构建器
  PendingMail.php             # 延迟发送
  SmtpDriver.php / SendmailDriver.php / LogDriver.php / ArrayDriver.php
  config.php
  migrations/
    0001_create_mail_log_table.php
```

发送邮件：
```php
$mail = (new Mailable())
    ->subject('订单确认')
    ->view('emails.order', ['order' => $order]);

mailer()->to($user->email)->send($mail);
```

---

## 15. 全局辅助函数

```php
// 应用
app()                       // 服务容器
app(Connection::class)     // 解析服务
config('key', 'default')    // 获取配置
config()->set('key', $val)  // 设置配置

// 路由
route('name', $params)       // 生成路由 URL
redirect('/path')            // 重定向响应
back()                       // 返回上一个 URL

// 数据库
db()                        // 获取 Connection
model(User::class)          // 模型查询

// 认证
auth()                       // AuthManager 实例
gate()                       // Gate 实例
user()                       // 当前用户（或 null）

// 会话
session()                    // Session 实例

// 响应
response()                   // ResponseFactory
response()->json($data)      // JSON 响应
response()->xml($data)       // XML 响应

// 视图
view('name', $data)          // 渲染视图
Element::make('div')         // Element 构建

// 事件
event(new RequestEvent($request))  // 分发类型化事件
emit('app.booted')                // 触发字符串事件
listen('event', $fn)              // 注册监听器
filter('hook', $value, $args)     // 链式过滤

// Module
modules()                    // ModuleManager
module('name')               // 获取指定 Module
notify($userId, $type, $title, $message)  // 发送通知
mailer()                     // MailManager

// 工具
logger()                     // LogManager
cache()                      // Cache 实例
env('KEY', 'default')        // 环境变量
now()                        // Carbon now
```

---

## 16. 中间件

### 16.1 全局中间件

在 `Kernel.php` 中注册，应用到所有请求：

```php
protected array $middleware = [
    \Framework\Http\Middleware\HandleExceptions::class,
    \Framework\Http\Middleware\StartSession::class,
    \Framework\Http\Middleware\ShareErrors::class,
];
```

### 16.2 路由中间件

```php
#[Middleware(AuthMiddleware::class)]
class UserController
{
    #[Middleware(ThrottleMiddleware::class, params: ['max' => 60])]
    public function update() { /* ... */ }
}
```

---

*文档版本: 0.2.0-dev*
*最后更新: 2026*
