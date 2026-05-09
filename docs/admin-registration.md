# Admin 注册机制文档

> Admin 模块的 Resource、Page、Widget、Setting、PageBuilder 组件注册机制。

---

## 目录

1. [架构概览](#1-架构概览)
2. [AdminManager — 核心注册中心](#2-adminmanager--核心注册中心)
3. [AdminServiceProvider — 启动引导](#3-adminserviceprovider--启动引导)
4. [Resource 注册机制](#4-resource-注册机制)
5. [Page 注册机制](#5-page-注册机制)
6. [Dashboard Widget 注册](#6-dashboard-widget-注册)
7. [Setting 注册机制](#7-setting-注册机制)
8. [PageBuilder 组件注册](#8-pagebuilder-组件注册)
9. [路由注册流程](#9-路由注册流程)
10. [侧边栏菜单生成](#10-侧边栏菜单生成)
11. [完整注册对照表](#11-完整注册对照表)

---

## 1. 架构概览

### 1.1 注册体系全景

```
AdminServiceProvider::boot()
  │
  ├── AdminManager::setPrefix()           设置 URL 前缀
  │
  ├── AdminManager::registerResource()    注册资源
  │   └── UserResource, RoleResource, PostResource, CategoryResource, TagResource
  │
  ├── AdminManager::registerPage()        注册页面
  │   └── MenuManagerPage, MediaPage, PageBuilderPage, PluginPage
  │
  ├── Register::boot()                    注册 Dashboard Widget
  │   └── 自动扫描 admin/DashboardData/*.php
  │
  ├── OptionsRegistry::register()         注册设置项
  │   └── GeneralOptions, SeoOptions, MailOptions, SecurityOptions, PerformanceOptions
  │
  ├── ComponentRegistry::register()       注册 PageBuilder 组件
  │   └── Header, Hero, Banner, FeatureGrid, CTA, Footer, Heading, TextBlock, ...
  │
  └── AdminManager::registerRoutes()      注册路由
      ├── 登录/登出路由
      ├── Dashboard 路由
      ├── Resource 路由（列表/创建/编辑）
      └── Page 路由（自定义/默认）
```

### 1.2 核心类关系

```
AdminManager (静态注册中心)
  ├── $resources: [name => class]         资源注册表
  ├── $pages: [name => class]             页面注册表
  ├── registerResource()                  注册资源
  ├── registerPage()                      注册页面
  ├── registerRoutes()                    注册路由
  └── bootFromConfig()                    从配置文件加载

AdminServiceProvider (启动引导)
  ├── register() → IoC 绑定
  └── boot() → 调用所有注册

ResourceInterface ← BaseResource ← 具体资源
  ├── getName()                           资源标识
  ├── getModel()                          关联模型
  ├── getTitle()                          显示标题
  ├── getRoutes()                         路由定义
  ├── configureForm()                     表单配置
  ├── configureTable()                    表格配置
  └── getLiveActions()                    LiveAction 注册

PageInterface ← BasePage / LiveComponent
  ├── getName()                           页面标识
  ├── getTitle()                          显示标题
  ├── getIcon()                           菜单图标
  ├── getGroup()                          菜单分组
  ├── getSort()                           排序权重
  └── getRoutes()                         路由定义
```

---

## 2. AdminManager — 核心注册中心

### 2.1 类定义

```php
class AdminManager
{
    protected static array $resources = [];   // [name => class]
    protected static array $pages = [];       // [name => class]
    protected static string $prefix = '/admin';
    protected static ?string $brandTitle = 'Admin';
    protected static bool $booted = false;
}
```

### 2.2 注册方法

#### registerResource()

```php
public static function registerResource(string $resource): void
{
    if (!is_subclass_of($resource, ResourceInterface::class)) {
        throw new \InvalidArgumentException("{$resource} must implement ResourceInterface");
    }
    static::$resources[$resource::getName()] = $resource;
}
```

- 参数为资源类的**全限定类名**（不是实例）
- 必须实现 `ResourceInterface`
- 以 `getName()` 返回值作为 key 存储

#### registerPage()

```php
public static function registerPage(string $page): void
{
    if (!is_subclass_of($page, PageInterface::class) && !is_subclass_of($page, AbstractLiveComponent::class)) {
        throw new \InvalidArgumentException("{$page} must implement PageInterface or extend AbstractLiveComponent");
    }
    static::$pages[$page::getName()] = $page;
}
```

- 支持 `PageInterface` 或 `AbstractLiveComponent` 两种类型
- 同样以 `getName()` 返回值作为 key

#### 查询方法

```php
public static function getResources(): array       // 返回所有资源类名（values）
public static function getPages(): array           // 返回所有页面类名（values）
public static function getResource(string $name): ?string  // 按 name 查找资源类
public static function getPage(string $name): ?string      // 按 name 查找页面类
```

#### 配置方法

```php
public static function setPrefix(string $prefix): void  // 设置 URL 前缀
public static function getPrefix(): string              // 获取 URL 前缀
public static function brand(string $title): void       // 设置品牌名
public static function getBrandTitle(): string          // 获取品牌名
```

---

## 3. AdminServiceProvider — 启动引导

### 3.1 register() — IoC 绑定

```php
public function register(): void
{
    $this->app->singleton(LifecycleManager::class, ...);
    $this->app->singleton(AuthManager::class, ...);
    $this->app->alias(AuthManager::class, 'auth');
    $this->app->singleton(Gate::class, ...);
    $this->app->bind(AdminAuthenticate::class, ...);
}
```

### 3.2 boot() — 注册入口

```php
public function boot(): void
{
    // 1. 设置 URL 前缀（支持环境变量）
    $prefix = env('ADMIN_PREFIX', '/admin');
    AdminManager::setPrefix($prefix);

    // 2. 扫描 Attribute 注解
    $adminDirs = [
        'admin/Resources',
        'admin/Pages',
        'admin/Components',
        'admin/DashboardData',
        'admin/Settings',
    ];
    foreach ($adminDirs as $dir) {
        LifecycleManager::getInstance()->scanAttributes($dir);
    }

    // 3. 注册 Resource
    AdminManager::registerResource(UserResource::class);
    AdminManager::registerResource(RoleResource::class);
    AdminManager::registerResource(PostResource::class);
    AdminManager::registerResource(CategoryResource::class);
    AdminManager::registerResource(TagResource::class);

    // 4. 注册 Page
    AdminManager::registerPage(MenuManagerPage::class);
    AdminManager::registerPage(MediaPage::class);
    AdminManager::registerPage(PageBuilderPage::class);
    AdminManager::registerPage(PluginPage::class);

    // 5. 注册 Dashboard Widget
    Register::boot($basePath);

    // 6. 注册所有路由
    AdminManager::registerRoutes($this->app->make(Router::class));
}
```

**要点**：
- Resource 和 Page 的注册是**显式调用**，不是自动扫描
- Dashboard Widget 是**自动扫描** `admin/DashboardData/*.php`
- Attribute 扫描在注册之前，确保 `#[AdminResource]` 等注解可用

---

## 4. Resource 注册机制

### 4.1 Resource 接口

```php
interface ResourceInterface
{
    public static function getName(): string;        // 资源标识（如 'posts', 'users'）
    public static function getModel(): string;       // 关联模型类名
    public static function getTitle(): string|array; // 显示标题（支持 intl）
    public static function getRoutes(): array;       // 路由定义
    public function configureForm(FormBuilder $form): void;   // 表单配置
    public function configureTable(DataTable $table): void;   // 表格配置
    public function getHeader(): mixed;              // 列表/表单页头部
    public function getFooter(): mixed;              // 列表/表单页底部
    public function getLiveActions(): array;          // LiveAction 注册
    public static function getFormWidth(): string;    // 表单宽度
}
```

### 4.2 #[AdminResource] 属性

```php
#[\Attribute(\Attribute::TARGET_CLASS)]
class AdminResource
{
    public function __construct(
        public string $name = '',          // 资源标识
        public string $model = '',         // 关联模型
        public string $title = '',         // 显示标题
        public string $icon = '',          // 菜单图标（Bootstrap Icons）
        public ?string $routePrefix = null,// 路由前缀
        public array $middleware = [],      // 中间件
        public string $group = '',         // 菜单分组
        public int $sort = 50,             // 排序权重
        public string $formWidth = '',     // 表单宽度 (sm/md/lg/xl/full)
    ) {}
}
```

### 4.3 BaseResource 默认实现

#### 名称解析

```php
protected static function resolveDefaultName(): string
{
    // UserResource → user
    // PostResource → post
    // CategoryResource → category
    $className = (new \ReflectionClass(static::class))->getShortName();
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Resource', '', $className)));
}
```

#### 模型解析

```php
protected static function resolveDefaultModel(): string
{
    // 优先从 #[AdminResource] 属性读取
    $attrs = (new \ReflectionClass(static::class))->getAttributes(AdminResource::class);
    if (!empty($attrs)) {
        return $attrs[0]->newInstance()->model;
    }
    return '';
}
```

#### 默认路由

```php
public static function getRoutes(): array
{
    $name = static::getName();
    return [
        "admin.resource.{$name}" => [
            'method' => 'GET',
            'path' => "/{$name}",
            'handler' => AdminListPage::resource($name),    // 列表页
        ],
        "admin.resource.{$name}.create" => [
            'method' => 'GET',
            'path' => "/{$name}/create",
            'handler' => AdminFormPage::resource($name),    // 创建页
        ],
        "admin.resource.{$name}.edit" => [
            'method' => 'GET',
            'path' => "/{$name}/{id}/edit",
            'handler' => AdminFormPage::resource($name),    // 编辑页
        ],
    ];
}
```

#### 表单宽度

```php
public static function getFormWidth(): string
{
    // 优先从 #[AdminResource(formWidth: 'lg')] 读取
    $attrs = (new \ReflectionClass(static::class))->getAttributes(AdminResource::class);
    if (!empty($attrs)) {
        $attr = $attrs[0]->newInstance();
        if (!empty($attr->formWidth)) return $attr->formWidth;
    }
    return 'md';  // 默认
}
```

### 4.4 Resource 注册示例

```php
#[AdminResource(
    name: 'posts',
    model: Post::class,
    title: '文章管理',
    icon: 'file-earmark-text',
    group: 'admin.content',
    sort: 11,
)]
class PostResource extends BaseResource
{
    public static function getName(): string { return 'posts'; }
    public static function getModel(): string { return Post::class; }
    public static function getTitle(): string|array { return ['admin:posts.title', [], '文章管理']; }

    // 自定义路由（覆盖默认）
    public static function getRoutes(): array
    {
        $prefix = AdminManager::getPrefix() ?: '/admin';
        return [
            'admin.posts' => [
                'method' => 'GET',
                'path' => '/posts',
                'handler' => AdminListPage::resource('posts'),
            ],
            'admin.posts.create' => [
                'method' => 'GET',
                'path' => '/posts/create',
                'handler' => fn() => PostEditPage::go(null),
            ],
            'admin.posts.edit' => [
                'method' => 'GET',
                'path' => '/posts/{id}/edit',
                'handler' => fn($id) => PostEditPage::go((int)$id),
            ],
        ];
    }

    public function configureForm(FormBuilder $form): void { ... }
    public function configureTable(DataTable $table): void { ... }
}
```

### 4.5 Resource 生命周期钩子

BaseResource 提供了完整的生命周期系统：

```php
// 列表页生命周期
const LIFECYCLE_LIST_BEFORE_HEADER = 'resource.list.before_header';
const LIFECYCLE_LIST_AFTER_HEADER  = 'resource.list.after_header';
const LIFECYCLE_LIST_BEFORE_TABLE  = 'resource.list.before_table';
const LIFECYCLE_LIST_AFTER_TABLE   = 'resource.list.after_table';
const LIFECYCLE_LIST_BEFORE_FOOTER = 'resource.list.before_footer';
const LIFECYCLE_LIST_AFTER_FOOTER  = 'resource.list.after_footer';

// 表单页生命周期
const LIFECYCLE_FORM_BEFORE_HEADER = 'resource.form.before_header';
const LIFECYCLE_FORM_AFTER_HEADER  = 'resource.form.after_header';
const LIFECYCLE_FORM_BEFORE_FORM   = 'resource.form.before_form';
const LIFECYCLE_FORM_AFTER_FORM    = 'resource.form.after_form';
const LIFECYCLE_FORM_BEFORE_FOOTER = 'resource.form.before_footer';
const LIFECYCLE_FORM_AFTER_FOOTER  = 'resource.form.after_footer';

// 数据操作生命周期
const LIFECYCLE_FORM_CREATING = 'resource.form.creating';
const LIFECYCLE_FORM_UPDATING = 'resource.form.updating';
const LIFECYCLE_FORM_CREATED  = 'resource.form.created';
const LIFECYCLE_FORM_UPDATED  = 'resource.form.updated';
const LIFECYCLE_TABLE_CONFIGURING = 'resource.table.configuring';
const LIFECYCLE_FORM_CONFIGURING  = 'resource.form.configuring';
```

**触发方式**：

```php
protected function fireLifecycle(string $hook, array $context = []): mixed
{
    $resourceName = static::getName();
    $fullHook = "{$hook}:{$resourceName}";

    Hook::fire($hook, $this, $context);          // 通用钩子
    Hook::fire($fullHook, $this, $context);      // 资源特定钩子

    return Hook::applyFilter($fullHook,
        Hook::applyFilter($hook, null, $this, $context),
        $this, $context
    );
}
```

**使用方式**：

```php
// 监听所有资源的表单配置
Hook::on('resource.form.configuring', function ($resource, $context) {
    $context['form']->hidden('tenant_id')->value(currentTenantId());
});

// 只监听 posts 资源的表单配置
Hook::on('resource.form.configuring:posts', function ($resource, $context) {
    $context['form']->hidden('author_id')->value(currentUserId());
});
```

### 4.6 Resource::page() — 自定义页面快捷方法

```php
public static function page(string $componentClass, array $props = []): \Closure
{
    $resourceName = static::getName();
    return function (...$args) use ($componentClass, $props, $resourceName) {
        $page = app()->make($componentClass);
        foreach ($props as $key => $value) {
            if (property_exists($page, $key)) {
                $page->$key = $value;
            }
        }
        if (method_exists($page, 'named')) {
            $shortName = strtolower((new \ReflectionClass($componentClass))->getShortName());
            $page->named("admin-res-{$resourceName}-{$shortName}");
        }
        $layout = new AdminLayout();
        $layout->activeMenu = $resourceName;
        $layout->setContent($page);
        return $layout;
    };
}
```

---

## 5. Page 注册机制

### 5.1 PageInterface

```php
interface PageInterface
{
    public static function getName(): string;         // 页面标识
    public static function getTitle(): string|array;  // 显示标题
    public static function getIcon(): string;         // 菜单图标
    public static function getGroup(): string;        // 菜单分组
    public static function getSort(): int;            // 排序权重
    public static function getRoutes(): array;        // 路由定义
}
```

### 5.2 两种 Page 实现方式

#### 方式一：BasePage（纯 PageInterface）

```php
abstract class BasePage implements PageInterface
{
    public static function getName(): string
    {
        // SettingPage → setting
        // MediaPage → media
        $className = (new \ReflectionClass(static::class))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Page', '', $className)));
    }

    public static function getRoutes(): array
    {
        $name = static::getName();
        return [
            "admin.page.{$name}" => [
                'method' => 'GET',
                'path' => "/{$name}",
                'handler' => [static::class, 'renderPage'],
            ],
        ];
    }

    public static function renderPage(): Response
    {
        $layout = new AdminLayout();
        $layout->activeMenu = static::getName();
        $page = new static();
        if (method_exists($page, 'named')) {
            $page->named('admin-page-' . static::getName());
        }
        $layout->setContent($page);
        return Response::html($layout);
    }
}
```

#### 方式二：LiveComponent + PageInterface

```php
class MediaPage extends LiveComponent implements PageInterface
{
    #[State] public string $viewMode = 'grid';
    #[State] public string $search = '';

    public static function getName(): string { return 'media'; }
    public static function getTitle(): string|array { return ['admin:media.title', [], '媒体库']; }
    public static function getIcon(): string { return 'folder2-open'; }
    public static function getGroup(): string { return ''; }
    public static function getSort(): int { return 20; }

    public static function getRoutes(): array
    {
        return [
            'admin.media' => [
                'method' => 'GET',
                'path' => '/media',
                'handler' => function () { return static::renderPage(); },
            ],
            'admin.media.upload' => [
                'method' => 'POST',
                'path' => '/media/upload',
                'handler' => [static::class, 'handleUpload'],
            ],
        ];
    }

    public static function renderPage()
    {
        $page = new static();
        $page->named('admin-page-media');
        $layout = new AdminLayout();
        $layout->activeMenu = 'media';
        $layout->setContent($page);
        return $layout;
    }
}
```

**对比**：

| 特性 | BasePage | LiveComponent + PageInterface |
|------|----------|------------------------------|
| 交互能力 | 无（纯静态页面） | 有 `#[State]` + `#[LiveAction]` |
| 路由 | 默认单路由 | 可自定义多路由 |
| 渲染 | `renderPage()` 静态方法 | `renderPage()` + `render()` |
| 状态管理 | 无 | Live 状态管理 |
| 适用场景 | 简单展示页 | 需要交互的页面 |

### 5.3 DashboardPage — 特殊页面

DashboardPage 不通过 `registerPage()` 注册，而是直接硬编码在 `AdminManager::registerRoutes()` 中：

```php
if (isset(static::$pages['dashboard'])) {
    $dashboardClass = static::$pages['dashboard'];
    $router->addRoute('GET', '/', [$dashboardClass, '__invoke'], 'dashboard');
    $router->addRoute('GET', '/dashboard', [$dashboardClass, '__invoke'], 'dashboard.alias');
}
```

同时在 `bootFromConfig()` 中自动注册：

```php
static::registerPage(DashboardPage::class);
static::registerPage(LoginPage::class);
static::registerPage(SettingPage::class);
```

---

## 6. Dashboard Widget 注册

### 6.1 Register 类

```php
class Register
{
    protected static array $widgets = [];

    public static function register(string $widgetClass): void
    {
        if (!is_subclass_of($widgetClass, AbstractLiveComponent::class)) {
            throw new \InvalidArgumentException("{$widgetClass} must extend AbstractLiveComponent");
        }
        static::$widgets[] = $widgetClass;
    }

    public static function getWidgets(): array
    {
        return static::$widgets;
    }
}
```

### 6.2 自动扫描注册

```php
public static function boot(string $basePath): void
{
    $dashboardDataPath = $basePath . '/admin/DashboardData';
    if (!is_dir($dashboardDataPath)) return;

    foreach (glob($dashboardDataPath . '/*.php') as $file) {
        if (basename($file) === 'Register.php') continue;  // 跳过自身

        $class = '\\Admin\\DashboardData\\' . basename($file, '.php');
        if (class_exists($class)) {
            static::register($class);
        }
    }
}
```

**要点**：
- 自动扫描 `admin/DashboardData/` 目录下所有 PHP 文件
- 跳过 `Register.php` 自身
- Widget 必须继承 `AbstractLiveComponent`
- 不需要手动调用 `register()`，放入目录即可

### 6.3 Widget 使用

```php
// DashboardPage 中
protected function renderDashboard(): UXComponent
{
    $widgets = Register::getWidgets();
    if (empty($widgets)) {
        return $this->renderEmptyDashboard();
    }

    $grid = Grid::make()->cols(3)->class('dashboard-widgets', 'gap-6');
    foreach ($widgets as $widgetClass) {
        $widget = new $widgetClass();
        $grid->child($widget->toHtml());
    }
    return $grid;
}
```

---

## 7. Setting 注册机制

### 7.1 OptionsRegistry 类

```php
class OptionsRegistry
{
    protected static array $options = [];              // 运行时值
    protected static array $optionDefinitions = [];    // 定义
    protected static bool $booted = false;

    public static function register(string $key, array $definition): void
    {
        static::$optionDefinitions[$key] = $definition;
    }

    public static function get(string $key, mixed $default = null): mixed { ... }
    public static function set(string $key, mixed $value): void { ... }
    public static function getAll(): array { ... }
    public static function getDefinition(string $key): ?array { ... }
    public static function getAllDefinitions(): array { ... }
    public static function getGroups(): array { ... }
    public static function getDefinitionsByGroup(string $group): array { ... }
    public static function update(array $data): void { ... }
}
```

### 7.2 Setting 定义格式

```php
OptionsRegistry::register('site_name', [
    'label' => ['admin:settings.site_name', [], '站点名称'],
    'type' => 'text',
    'default' => 'My Site',
    'group' => ['admin.settings.general', [], '常规'],
    'description' => '网站显示名称',
]);
```

### 7.3 Setting 文件

| 文件 | 分组 | 说明 |
|------|------|------|
| GeneralOptions.php | admin.settings.general | 常规设置（站点名、描述等） |
| SeoOptions.php | admin.settings.seo | SEO 设置 |
| MailOptions.php | admin.settings.mail | 邮件设置 |
| SecurityOptions.php | admin.settings.security | 安全设置 |
| PerformanceOptions.php | admin.settings.performance | 性能设置 |

---

## 8. PageBuilder 组件注册

### 8.1 ComponentRegistry 类

```php
class ComponentRegistry
{
    private static array $types = [];
    private static bool $booted = false;

    public static function register(ComponentType $type): void
    {
        self::$types[$type->name()] = $type;
    }

    public static function get(string $name): ?ComponentType { ... }
    public static function all(): array { ... }
    public static function byCategory(): array { ... }
}
```

### 8.2 自动注册

```php
private static function boot(): void
{
    if (self::$booted) return;
    self::$booted = true;

    // Sections
    self::register(new Sections\Header());
    self::register(new Sections\Hero());
    self::register(new Sections\Banner());
    self::register(new Sections\FeatureGrid());
    self::register(new Sections\CTA());
    self::register(new Sections\Footer());

    // Basic
    self::register(new Basic\Heading());
    self::register(new Basic\TextBlock());
    self::register(new Basic\ImageBlock());
    self::register(new Basic\ButtonBlock());
    self::register(new Basic\Divider());

    // Layout
    self::register(new Layout\GridContainer());
    self::register(new Layout\Columns());
}
```

### 8.3 ComponentType 接口

每个 PageBuilder 组件实现 `ComponentType` 抽象类：

```php
abstract class ComponentType
{
    abstract public function name(): string;       // 组件标识
    abstract public function label(): string;      // 显示名称
    abstract public function category(): string;   // 分类（sections/basic/layout/media）
    abstract public function icon(): string;       // 图标
    abstract public function render(array $settings): Element;  // 渲染 Element

    public function settings(FormBuilder $form): void { }
    public function styleTargets(): array { return ['root' => '根容器']; }
    public function isContainer(): bool { return false; }
}
```

### 8.5 Slot 插槽系统

布局类组件（GridContainer、Columns）支持 **Slot 插槽**，允许子组件插入到指定区域。

#### 插槽定义

```php
// ComponentType 基类方法
public function slots(array $settings = []): array { return []; }
public function slotLimits(array $settings = []): array { return []; }
public function getSlotElement(Element $rendered, string $slotName): Element { return $rendered; }
```

| 方法 | 用途 |
|------|------|
| `slots($settings)` | 返回插槽定义数组 `[['name' => 'col_1', 'label' => '列 1'], ...]` |
| `slotLimits($settings)` | 每个 slot 的子组件上限，`null` = 无限制 |
| `getSlotElement($rendered, $slotName)` | 返回渲染后的 Element 中该 slot 对应的子元素（默认返回根元素） |

#### GridContainer 示例

```php
class GridContainer extends ComponentType
{
    public function slots(array $settings = []): array
    {
        $count = (int)($settings['columns'] ?? 2);
        $slots = [];
        for ($i = 1; $i <= $count; $i++) {
            $slots[] = ['name' => "col_{$i}", 'label' => "列 {$i}"];
        }
        return $slots;
    }
}
```

GridContainer 的 slot 直接挂载到根元素（CSS Grid 自动排列子元素），不需要覆盖 `getSlotElement()`。

#### Columns 示例

```php
class Columns extends ComponentType
{
    public function slots(array $settings = []): array
    {
        $count = (int)($settings['count'] ?? 2);
        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            $slots[] = ['name' => "col_{$i}", 'label' => "列 " . ($i + 1)];
        }
        return $slots;
    }

    public function getSlotElement(Element $rendered, string $slotName): Element
    {
        $index = substr($slotName, 4);
        foreach ($rendered->getChildren() as $child) {
            if ($child instanceof Element && $child->getAttr('data-pb-style') === "column_{$index}") {
                return $child;
            }
        }
        return $rendered;
    }
}
```

Columns 的 slot 对应到内部 `data-pb-style="column_{N}"` 的 div，需要覆盖 `getSlotElement()` 确保子组件渲染到正确的列容器内。

#### 树结构

```json
{
  "uid": "x",
  "type": "columns",
  "settings": { "count": 2 },
  "slots": {
    "col_0": [{ "uid": "a", "type": "heading", "settings": {} }],
    "col_1": [{ "uid": "b", "type": "text_block", "settings": {} }]
  }
}
```

#### 渲染流程

```
PageRenderer::renderTree():
  for each component:
    render(settings) → element
    for each slot:
      getSlotElement(element, slotName) → targetEl
      renderTree(slotItems, targetEl)   ← 子组件注入到目标元素
    parent->child(element)
```

#### 上限校验

在 `PageBuilderPage` 的 `addChildComponent()` 和 `updateComponentTree()` 中自动校验 `slotLimits()`，超出上限时拒绝操作并 toast 提示。

### 8.6 自包含组件

Section 类组件（Header、Hero、Banner、FeatureGrid、CTA、Footer）不覆盖 `slots()`，保持默认空数组，不接受子组件。插入即完整区块。

### 8.7 分类

```php
public static function categories(): array
{
    return [
        'sections' => '区块',
        'basic'    => '基础',
        'layout'   => '布局',
        'media'    => '媒体',
    ];
}
```

---

## 9. 路由注册流程

### 9.1 AdminManager::registerRoutes()

```php
public static function registerRoutes(Router $router): void
{
    static::bootFromConfig();
    $prefix = static::getPrefix();

    // 1. 登录路由（无中间件）
    $router->addRoute('GET', $prefix . '/login', [$loginClass, '__invoke'], 'admin.login');
    $router->addRoute('POST', $prefix . '/login', [$loginClass, '__invoke'], 'admin.login.handle');

    // 2. 登出路由
    $router->addRoute('GET', $prefix . '/logout', ..., 'admin.logout');

    // 3. 需要认证的路由组
    $router->group([
        'prefix' => $prefix,
        'middleware' => [AdminAuthenticate::class],
        'name' => 'admin',
    ], function (Router $router) {

        // 3a. Dashboard
        $router->addRoute('GET', '/', [$dashboardClass, '__invoke'], 'dashboard');

        // 3b. Resource 路由
        foreach (static::$resources as $resourceClass) {
            $routes = $resourceClass::getRoutes();
            foreach ($routes as $name => $config) {
                $router->addRoute($config['method'], $config['path'], $config['handler'], $name);
            }
        }

        // 3c. Page 路由
        foreach (static::$pages as $name => $pageClass) {
            if (in_array($name, ['dashboard', 'login'], true)) continue;

            if (method_exists($pageClass, 'getRoutes')) {
                // 自定义路由
                $routes = $pageClass::getRoutes();
                foreach ($routes as $routeName => $config) {
                    $router->addRoute($config['method'], $config['path'], $config['handler'], $routeName);
                }
            } else {
                // 默认路由：GET /{name}
                $router->addRoute('GET', '/' . $name, [$pageClass, '__invoke'], 'page.' . $name);
            }
        }
    });
}
```

### 9.2 路由注册优先级

```
1. 登录/登出路由（无中间件保护）
2. Dashboard 路由
3. Resource 路由（按注册顺序）
4. Page 路由（按注册顺序，跳过 dashboard 和 login）
```

### 9.3 Resource 默认路由结构

| 路由名 | 方法 | 路径 | Handler |
|--------|------|------|---------|
| `admin.resource.{name}` | GET | `/{name}` | `AdminListPage::resource($name)` |
| `admin.resource.{name}.create` | GET | `/{name}/create` | `AdminFormPage::resource($name)` |
| `admin.resource.{name}.edit` | GET | `/{name}/{id}/edit` | `AdminFormPage::resource($name)` |

### 9.4 AdminListPage::resource() 工厂方法

```php
public static function resource(string $resourceName): \Closure
{
    return function () use ($resourceName) {
        $page = new static();
        $page->resourceName = $resourceName;
        $page->named("admin-list-{$resourceName}");

        $layout = new AdminLayout();
        $layout->activeMenu = $resourceName;
        $layout->setContent($page);

        return $layout;
    };
}
```

### 9.5 AdminFormPage::resource() 工厂方法

```php
public static function resource(string $resourceName): \Closure
{
    return function ($id = null) use ($resourceName) {
        $page = new static();
        $page->resourceName = $resourceName;
        if ($id !== null) {
            $page->recordId = (int)$id;
        }
        // ... 设置 layout
    };
}
```

---

## 10. 侧边栏菜单生成

### 10.1 AdminLayout::getMenuGroups()

侧边栏菜单由 `AdminLayout` 自动从 `AdminManager` 获取所有注册的 Resource 和 Page 生成：

```php
protected function getMenuGroups(): array
{
    $groups = [];

    // 1. Dashboard（固定在顶部）
    $groups[''][] = [
        'name' => 'dashboard',
        'title' => ['admin.dashboard', [], '控制台'],
        'url' => $prefix,
        'icon' => 'speedometer2',
        'sort' => 0,
    ];

    // 2. 从 Resource 生成菜单项
    foreach (AdminManager::getResources() as $resourceClass) {
        $ref = new \ReflectionClass($resourceClass);
        $attrs = $ref->getAttributes(AdminResource::class);
        if (!empty($attrs)) {
            $attr = $attrs[0]->newInstance();
            $group = $attr->group;      // 从 #[AdminResource] 读取分组
            $icon = $attr->icon;        // 从 #[AdminResource] 读取图标
            $sort = $attr->sort;        // 从 #[AdminResource] 读取排序
        }
        $groups[$group][] = [
            'name' => $resourceClass::getName(),
            'title' => $resourceClass::getTitle(),
            'url' => $prefix . '/' . $resourceClass::getName(),
            'icon' => $icon,
            'sort' => $sort,
        ];
    }

    // 3. 从 Page 生成菜单项
    foreach (AdminManager::getPages() as $pageClass) {
        $name = $pageClass::getName();
        if (in_array($name, ['dashboard', 'login'], true)) continue;

        $groups[$pageClass::getGroup()][] = [
            'name' => $name,
            'title' => $pageClass::getTitle(),
            'url' => $prefix . '/' . $name,
            'icon' => $pageClass::getIcon(),
            'sort' => $pageClass::getSort(),
        ];
    }

    // 4. 按 sort 排序
    foreach ($groups as &$items) {
        usort($items, fn($a, $b) => ($a['sort'] ?? 50) <=> ($b['sort'] ?? 50));
    }

    return $sorted;
}
```

### 10.2 分组映射

```php
protected function resolveGroupLabel(string $groupName): string|array
{
    $groupMap = [
        'admin.system'  => ['admin.groups.system', [], '系统管理'],
        'admin.content' => ['admin.groups.content', [], '内容管理'],
    ];
    return $groupMap[$groupName] ?? $groupName;
}
```

### 10.3 菜单结构示例

```
┌─────────────────────────┐
│ 🏠 控制台 (dashboard)    │  ← 空分组，固定顶部
├─────────────────────────┤
│ 内容管理                 │  ← admin.content 分组
│   📄 文章管理 (posts)    │    sort: 11
│   📂 分类管理 (categories)│   sort: 12
│   🏷 标签管理 (tags)     │    sort: 13
│   📁 媒体库 (media)      │    sort: 20
├─────────────────────────┤
│ 系统管理                 │  ← admin.system 分组
│   👥 用户管理 (users)    │    sort: 51
│   🔑 角色管理 (roles)    │    sort: 52
│   📋 菜单管理 (menu_manager)│ sort: 53
│   🔌 插件管理 (plugin)   │    sort: 60
└─────────────────────────┘
```

---

## 11. 完整注册对照表

### 11.1 注册方式对照

| 注册对象 | 注册方式 | 注册时机 | 存储 |
|---------|---------|---------|------|
| Resource | `AdminManager::registerResource()` | AdminServiceProvider::boot() | `AdminManager::$resources` |
| Page | `AdminManager::registerPage()` | AdminServiceProvider::boot() | `AdminManager::$pages` |
| Dashboard Widget | 自动扫描 `admin/DashboardData/*.php` | `Register::boot()` | `Register::$widgets` |
| Setting | `OptionsRegistry::register()` | 各 Options 文件 | `OptionsRegistry::$optionDefinitions` |
| PageBuilder 组件 | `ComponentRegistry::register()` | `ComponentRegistry::boot()` | `ComponentRegistry::$types` |
| 路由 | `AdminManager::registerRoutes()` | AdminServiceProvider::boot() | Router |

### 11.2 Resource vs Page 对照

| 特性 | Resource | Page |
|------|----------|------|
| 接口 | `ResourceInterface` | `PageInterface` |
| 基类 | `BaseResource` | `BasePage` 或 `LiveComponent` |
| 属性 | `#[AdminResource]` | 无 |
| 注册方法 | `AdminManager::registerResource()` | `AdminManager::registerPage()` |
| 默认路由 | 列表/创建/编辑 三条 | 单条 GET 路由 |
| 菜单来源 | `#[AdminResource]` 的 icon/group/sort | `PageInterface` 的 getIcon/getGroup/getSort |
| 表单配置 | `configureForm()` | 无 |
| 表格配置 | `configureTable()` | 无 |
| 生命周期 | 完整 Hook 系统 | 无 |
| 渲染页面 | `AdminListPage` + `AdminFormPage` | 自定义 `renderPage()` |

### 11.3 创建新 Resource 的步骤

```php
// 1. 创建 Resource 类
#[AdminResource(
    name: 'products',
    model: Product::class,
    title: '产品管理',
    icon: 'box-seam',
    group: 'admin.content',
    sort: 14,
    formWidth: 'lg',
)]
class ProductResource extends BaseResource
{
    public static function getName(): string { return 'products'; }
    public static function getModel(): string { return Product::class; }
    public static function getTitle(): string|array { return ['admin:products.title', [], '产品管理']; }

    public function configureForm(FormBuilder $form): void
    {
        $form->schema([
            Section::make('基本信息')->schema([
                TextInput::make('name')->label('名称')->required(),
                TextInput::make('price')->label('价格')->number(),
            ]),
        ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
              ->column('name', '名称')
              ->column('price', '价格');
    }
}

// 2. 在 AdminServiceProvider::boot() 中注册
AdminManager::registerResource(ProductResource::class);
```

### 11.4 创建新 Page 的步骤

```php
// 1. 创建 Page 类
class AnalyticsPage extends LiveComponent implements PageInterface
{
    #[State] public string $period = '7d';

    public static function getName(): string { return 'analytics'; }
    public static function getTitle(): string|array { return ['admin:analytics.title', [], '数据分析']; }
    public static function getIcon(): string { return 'graph-up'; }
    public static function getGroup(): string { return ''; }
    public static function getSort(): int { return 15; }

    public static function getRoutes(): array
    {
        return [
            'admin.analytics' => [
                'method' => 'GET',
                'path' => '/analytics',
                'handler' => function () {
                    $page = new static();
                    $page->named('admin-page-analytics');
                    $layout = new AdminLayout();
                    $layout->activeMenu = 'analytics';
                    $layout->setContent($page);
                    return $layout;
                },
            ],
        ];
    }

    public function render(): Element { ... }
}

// 2. 在 AdminServiceProvider::boot() 中注册
AdminManager::registerPage(AnalyticsPage::class);
```
