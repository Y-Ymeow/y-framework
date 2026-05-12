# 框架开发指南 (AGENT.md)

本文件定义了该 PHP 框架的核心开发模式与工程标准，开发时必须严格遵守。

---

## 1. 目录结构

```
app/                  # 应用层代码
admin/                # 管理后台模块
  ├─ Pages/          # 页面 (DashboardPage, MediaPage 等)
  ├─ Services/       # AdminManager, ActivityLogger
  ├─ Resources/      # 资源管理
  └─ PageBuilder/    # 页面构建器
framework/            # 核心框架代码
  ├─ Foundation/     # Application, Container, Kernel
  ├─ HTTP/           # Request, Response, Session, Middleware
  ├─ Routing/        # 路由系统 (Route, RouteGroup, Middleware 属性)
  ├─ View/           # Element, Document, AssetRegistry, CssCollector
  ├─ Component/      # LiveComponent 系统
  ├─ UX/             # UX 组件库
  ├─ Database/       # ORM (Model, Query Builder, Schema)
  ├─ Auth/           # AuthManager, 认证系统
  ├─ Cache/          # 缓存系统
  ├─ Queue/          # QueueManager, Job, 队列系统
  ├─ Events/         # EventDispatcher, 事件系统
  ├─ Validation/     # 验证器
  ├─ CSS/            # CSSEngine, Tailwind 生成
  ├─ Config/         # ConfigManager, 配置管理
  ├─ Module/         # ModuleManager, 模块系统
  ├─ Intl/           # Translator, 国际化
  ├─ Install/        # InstallManager, InstallController
  ├─ Scheduler/      # 定时任务调度
  ├─ Log/            # 日志系统
  ├─ DebugBar/       # 调试栏
  ├─ Console/        # 命令系统 (Commands/)
resources/js/         # 前端源资源 (Vite)
public/build/         # Vite 构建输出
database/             # 数据库迁移与种子
config/               # 配置文件 (app.php, database.php 等)
```

---

## 2. View 系统 (Element)

### 核心类
- `Framework\View\Base\Element` - 基础元素类，声明式构建 HTML
- `Framework\View\Element\Container` - 容器（div, section, main 等）
- `Framework\View\Element\Text` - 文本标签（h1-h6, p, span 等）
- `Framework\View\Document\Document` - HTML 文档包装器

### 核心方法
```php
Element::make('div')                    // 工厂方法
$el->id('container')                     // 设置 id
$el->class('p-4', 'bg-white')           // 添加 CSS 类
$el->attr('data-id', '123')             // 设置属性
$el->text('Hello')                      // 纯文本（自动转义）
$el->html('<b>Bold</b>')                // HTML 内容（sanitize 过滤）
$el->child($child)                      // 添加子元素
$el->children($a, $b, $c)              // 添加多个子元素
$el->cloak()                            // 防闪烁 [data-cloak]
```

### 安全机制
- 属性层过滤 `on*` 事件
- 内容层过滤 `javascript:`/`data:` 协议
- HTML 内容白名单 sanitize

### Tailwind 快捷方法
使用 traits：`HasTailwindAppearance`, `HasTailwindLayout`, `HasTailwindSpacing`, `HasTailwindTypography`

### Document 自动包装
`Response::html($element)` 自动检测内容，无 `<html>` 时用 `Document` 包装。

---

## 3. Live 系统 (y-live)

### 核心类
- `Framework\Component\Live\AbstractLiveComponent` - Live 组件基类
- `Framework\Component\Live\LiveResponse` - 响应对象
- `Framework\Component\Live\LiveRequestHandler` - 请求处理器

### 生命周期
`boot()` → `mount()` → `hydrate()` → actions → `dehydrate()`

### 属性系统
```php
#[Prop]              // 从父组件传入的属性
#[State]             // 组件内部状态
#[Persistent]        // 持久化
#[Computed]          // 计算属性
#[LiveAction]        // 可被前端调用的方法
```

### 通信端点
- `POST /live/action` - 执行 Action
- `POST /live/state` - 轻量状态更新
- `POST /live/event` - 事件派发

### LiveResponse 链式 API
```php
LiveResponse::make()
    ->fragment('name', $html)     // 分片更新
    ->toast('消息', 'success')    // Toast 提示
    ->openModal('modal-id')       // 打开弹窗
    ->closeModal('modal-id')      // 关闭弹窗
    ->redirect('/url')            // 跳转
    ->dispatch('event', $detail)  // 派发事件
```

### 分片更新
```php
// 1. 在 render() 中标记
$el->liveFragment('name')

// 2. Action 中返回
return LiveResponse::make()->fragment('name', $newHtml)
```

### 内置指令方法
`$this->toast()`, `$this->openModal()`, `$this->ux()`, `$this->redirect()`

---

## 4. UX 组件 (y-ux)

### 目录结构
```
framework/UX/
├── UXComponent.php          # UX 组件基类
├── UXLiveComponent.php      # Live 集成基类
├── UI/                      # Button, Accordion
├── Form/                    # 表单组件
│   ├── FormBuilder.php      # 表单构建器
│   ├── Components/          # 字段组件 (TextInput, Select, DatePicker...)
│   ├── RichEditor.php       # 富文本编辑器
│   └── ...
├── Data/                    # DataTable, DataGrid, Calendar
├── Dialog/                  # Modal, Drawer, Toast, ConfirmDialog
├── Navigation/              # Tabs, Pagination, Breadcrumb
├── Feedback/                # Alert, Loading, Progress, Skeleton
└── Chart/                  # 图表
```

### 使用方式
```php
DataTable::make()
    ->columns(['name' => '姓名', 'email' => '邮箱'])
    ->rows($users)
    ->sortable()
    ->searchable()
    ->rowActions(fn($row) => [
        Button::make()->label('编辑')->liveAction('edit', $row['id'])
    ]);

Modal::make()
    ->title('确认')
    ->content('确定删除？')
    ->ok('确定', 'deleteAction', 'danger')
    ->cancel('取消');
```

### Live 集成
```php
// 触发 Action
->liveAction('actionName')

// 双向绑定
->liveModel('propertyName')

// 事件派发
->on('change', 'console.log(event.detail)')
```

### CSS 约定
所有样式以 `.ux-` 为前缀确保隔离

---

## 5. 前端架构 (y-directive + y-live + y-ux)

### 目录结构
```
resources/js/
├── y-directive/            # 响应式指令引擎
│   ├── directives/         # 指令定义 (if, for, model, bind, on...)
│   ├── reactive/           # 响应式核心 (track/trigger/effect)
│   ├── evaluator/          # 表达式求值
│   └── runner/             # 指令执行器
├── y-live/                 # Live 前端
│   ├── core/
│   │   ├── connection.js   # HTTP 通信
│   │   └── live-proxy.js   # $live 代理
│   ├── operations.js       # 操作执行
│   └── navigate.js         # 客户端导航
├── y-ux/                   # UX 组件前端
│   ├── components/         # 组件实现
│   └── css/                # 样式
├── ui.js                   # 入口 (y-directive + y-live)
└── ux.js                   # 入口 (y-ux)
```

### 指令优先级
```
if(10) → for(20) → show(30) → model(40) → bind(50) → text(60) → on(70) → effect(80)
```

### $live 代理 API
```javascript
$live.actionName(params)  // 调用后端 LiveAction
$live.update(prop, value)  // 更新属性到后端
$live.refresh(name)        // 刷新片段
```

### 事件委托
`ux.js` 必须使用全局事件委托，确保 DOM Patch 后交互有效。

---

## 6. 资源管理 (Vite + Assets)

### Vite 配置
```javascript
// vite.config.js
base: process.env.NODE_ENV === 'development' ? '/' : '/_framework/'
entry: ui.js, ux.js
outDir: ./public/build
```

### 构建命令
```bash
cd resources/js && npm run build
# 或
cd resources/js && bun run build
```

### Asset 类
```php
Asset::isDev()              // 检测开发模式
Asset::dist('ui.js')       // 获取构建资源 URL
Asset::distCss('ui.js')     // 获取 CSS 文件列表
```

### AssetRegistry
```php
AssetRegistry::getInstance()
    ->css('/custom.css')
    ->js('/app.js')
    ->ui()                  // 加载 ui.js/css
    ->ux()                  // 加载 ux.js/css
```

---

## 7. 路由系统

### 属性定义
```php
#[Route('/path', methods: ['GET'], name: 'name', middleware: ['auth'])]
class Controller {
    #[Route('/action', ['POST'], name: 'action')]
    public function doAction() {}
}

#[RouteGroup('/prefix', middleware: ['auth'], name: 'group.')]
#[Middleware(['throttle'])]
class ApiController {}
```

### 路由中间件别名
`auth`, `guest`, `throttle`, `csrf` 等已在 `MiddlewareManager` 中预定义。

### 缓存命令
```bash
php bin/console route:cache
php bin/console cache:clear route
```

---

## 8. 数据库 / ORM

### Model 基类
```php
class User extends Model {
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected array $casts = ['created_at' => 'datetime'];
}
```

### 关系
```php
$user->posts()           // hasMany
$post->author()          // belongsTo
$user->roles()           // belongsToMany
$comment->commentable()  // morphMany/morphTo
```

### 事件
```php
Model::creating(fn($m) => ...);  // 保存前
Model::created(fn($m) => ...);   // 保存后
```

### 多语言 (HasTranslations)
```php
trait HasTranslations;
protected array $translatable = ['name', 'description'];
$product->name = 'Hello';           // 自动存储到当前语言
$product->setTranslation('name', 'zh', '你好');
$product->getTranslation('name', 'zh');  // '你好'
```

### 迁移
```bash
php bin/console migrate
php bin/console db:seed
```

---

## 9. Console 命令系统

### 常用命令
```bash
# 数据库
php bin/console migrate                 # 执行迁移
php bin/console migrate --seed          # 迁移后执行种子
php bin/console migrate:rollback        # 回滚迁移
php bin/console db:seed                 # 执行种子

# 代码生成
php bin/console make:model User        # 生成 Model
php bin/console make:migration create_users  # 生成迁移
php bin/console make:component UserTable  # 生成 LiveComponent
php bin/console make:middleware Auth   # 生成中间件
php bin/console make:job SendEmail     # 生成 Job
php bin/console make:event UserCreated # 生成事件
php bin/console make:admin-resource User # 生成 Admin Resource

# 缓存
php bin/console route:cache            # 路由缓存
php bin/console cache:clear             # 清除缓存
php bin/console cache:clear route       # 清除路由缓存
php bin/console cache:clear config      # 清除配置缓存

# CSS
php bin/console css:generate           # 生成 Tailwind CSS

# 其他
php bin/console key:generate           # 生成 APP_KEY
php bin/console route:list             # 列出所有路由
```

---

## 10. 配置系统

### ConfigManager 用法
```php
// 获取配置
config('app.debug');                    // 获取嵌套配置
config('app.locale', 'zh');             // 带默认值

// 设置配置（运行时）
config()->set('app.locale', 'en');

// 合并配置
config()->merge($existing, $user);

// 环境变量
env('DB_HOST', 'localhost');            // 嵌套支持
```

### 缓存机制
- 生产环境自动缓存到 `storage/cache/config.php`
- 检测配置变更后自动失效

---

## 11. 认证系统

### AuthManager
```php
// 登录
Auth::attempt(['email' => 'x@x.com', 'password' => 'xxx']);

// 获取用户
$user = Auth::user();

// 退出
Auth::logout();

// 检查登录
if (Auth::check()) { ... }

// 守卫
Auth::guard('web')->check();
```

### Auth 中间件
```php
#[Middleware(['auth'])]      // 需要登录
#[Middleware(['guest'])]     // 需要未登录
```

---

## 12. 队列系统

### QueueManager
```php
// 推送任务
Queue::push(SendEmailJob::class, ['email' => 'x@x.com']);
Queue::push(fn() => Mail::send(...));  // 闭包

// 延迟
Queue::push($job, $data, null, 60);     // 60秒后执行

// 批量
Queue::pushMany([
    ['class' => Job1::class, 'data' => []],
    ['class' => Job2::class, 'data' => []],
]);

// Worker
php bin/console queue:work
```

### Job 类
```php
class SendEmailJob implements JobInterface
{
    public function handle(): void
    {
        // 处理逻辑
    }
}
```

---

## 13. CSS 生成系统

### CSSEngine
自动生成 Tailwind CSS，支持：
- 响应式断点：`sm`, `md`, `lg`, `xl`, `2xl`
- 伪类：`hover`, `focus`, `active`, `disabled`
- 媒体查询：`max-sm`, `max-md` 等

### 常用类生成
```bash
php bin/console css:generate
```

输出到 `public/build/css/generated.css`

---

## 14. Admin 后台结构

### 页面
```php
// admin/Pages/DashboardPage.php
// admin/Pages/MediaPage.php
// admin/Pages/MenuManagerPage.php
```

### 服务
```php
// admin/Services/AdminManager.php  - 管理器
// admin/Services/ActivityLogger.php - 活动日志
```

### 资源
```php
// admin/Resources/UserResource.php - 数据资源
php bin/console make:admin-resource User
```

### 页面构建器
```php
// admin/PageBuilder/ 允许拖拽构建页面
```

---

## 15. 国际化 (Intl)

### Translator
```php
// 翻译
trans('messages.welcome');
trans('messages.greeting', ['name' => 'John']);

// 复数
trans_choice('messages.items', $count);

// 切换语言
translator()->setLocale('zh');
```

### 语言包位置
```
resources/lang/
├── en/messages.php
├── zh/messages.php
├── en/ux/pagination.php
├── zh/ux/pagination.php
```

---

## 16. 模块系统

### BaseModule
```php
class MyModule implements ModuleInterface
{
    public function getName(): string { return 'my-module'; }
    public function isEnabled(): bool { return true; }
    public function getDependencies(): array { return []; }
    public function getServiceProvider(): string { return MyModuleProvider::class; }
    public function getConfigFile(): ?string { return __DIR__ . '/config.php'; }
    public function getMigrationsPath(): ?string { return __DIR__ . '/migrations'; }
}
```

### ModuleManager
```php
$manager->register($module);
$manager->boot();
$manager->getMigrations();  // 收集模块迁移
```

---

## 17. 组件属性详解

| 属性 | 用途 | 位置 |
|------|------|------|
| `#[Prop]` | 父组件传入属性 | 属性 |
| `#[State]` | 组件内部状态 | 属性 |
| `#[Persistent]` | 长期持久化 | 属性 |
| `#[Computed]` | 计算属性 | 属性 |
| `#[Locked]` | 不可修改 | 属性 |
| `#[LiveAction]` | 前端可调用方法 | 方法 |
| `#[Middleware]` | 中间件绑定 | 类/方法 |
| `#[Route]` | 路由定义 | 类/方法 |
| `#[RouteGroup]` | 路由组 | 类 |

---

## 18. 注意事项

- **序列化**：`operations` 属性已排除在序列化外
- **构建**：修改 `resources/js/` 后必须重新构建
- **事件委托**：前端交互必须使用事件委托
- **操作管道**：`y-live` 的 `dispatchAction` 必须使用 `L.executeOperation()`
- **XSS 防护**：避免 `attr('onclick', ...)` 等危险用法
- **多语言**：使用 `HasTranslations` trait 在单个字段存储 JSON
- **配置缓存**：生产环境会自动缓存，修改配置后清除缓存
- **模块依赖**：模块需先注册依赖才能被依赖