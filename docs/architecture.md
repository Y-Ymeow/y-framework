# 框架架构文档

## 核心设计哲学
- **Route-First**: 路由优先，通过属性注入实现高性能路由。
- **Component-Driven**: 组件化交互，服务器驱动的响应式前端。
- **Lightweight**: 追求极致轻量，移除冗余依赖，提供原生的调试体验。

## 缓存与性能优化
### 路由缓存
通过 `php bin/console route:cache` 生成 `storage/cache/routes.php`，框架在启动时会优先加载此文件，避免文件扫描。

### 组件缓存
通过 `php bin/console live:cache` 生成 `storage/cache/live_components.php`，预编译 LiveAction 映射，消除运行时反射开销。

### 状态编码
LiveComponent 状态通过 `base64(binary_sig + gzcompress(serialize(data)))` 编码，体积较原始 JSON+Base64 缩减约 50%。

## 命令行工具 (CLI)
框架提供了类似 Artisan 的交互工具，支持自动发现 `src/Console/Commands` 与 `app/Commands` 下的命令。

- `make:component {name}`: 快速生成 LiveComponent 类。
- `make:migration {name}`: 创建数据库迁移文件。
- `migrate`: 执行数据库迁移。
- `migrate:rollback`: 回滚数据库迁移。
- `route:cache`: 编译路由缓存。
- `route:list`: 展示所有注册的路由及其中间件。
- `live:cache`: 编译组件 Action 缓存。
- `key:generate`: 自动生成并设置 APP_KEY。

## 错误处理与调试
内置自定义错误处理模块，提供现代化的深色主题调试页面，支持：
- **源码预览**: 点击报错行可预览上下文代码。
- **上下文调试**: 展示完整的 Request 信息（Headers, POST/GET）及当前 Session/Cookie。
- **无依赖**: 彻底移除 `spatie/ignition`，保证框架简洁。

## 核心模块

### 基础设施
- **Application**: 应用容器，基于 php-di 实现依赖注入。
- **Kernel**: 应用内核，处理请求生命周期。
- **Router**: 路由系统，支持 Attribute 和手动注册。

### 数据层
- **Connection**: PDO 数据库连接管理。
- **QueryBuilder**: 链式查询构建器。
- **Model**: ORM 模型基类，支持关联关系。
- **Schema**: 表结构构建器。
- **Migration**: 数据库迁移系统。

### 认证授权
- **AuthManager**: 认证管理器，支持 Session 和 Remember Me。
- **User**: 用户模型基类，包含密码哈希验证。

### 文件存储
- **Storage**: 文件系统操作，基于 Flysystem。
- **Asset**: 资源链接生成，支持版本控制。
- **StaticFile**: 静态文件服务。
- **ImageServer**: 图片处理服务。
- **FileDownloadRoute**: 大文件下载，支持断点续传。

### 缓存与日志
- **LogManager**: 实现 `Psr\Log\LoggerInterface`，支持 `single` 和 `daily` 驱动。
- **CacheManager**: 多驱动缓存管理器，支持文件、Redis、内存。

### 前端组件
- **LiveComponent**: 服务端驱动的响应式组件。
- **UI**: 基于 Tailwind CSS 的 UI 组件库。
- **Data 组件**: DataTable、DataList、DataGrid、DataCard、DataTree 等数据展示组件。
- **Admin 布局**: 完整的后台管理系统布局，包含侧边栏、header、footer。

### Admin 系统
- **AdminResourceController**: 通用的 Resource CRUD 控制器，处理所有 Resource 的路由。
- **AdminLayout**: Admin 布局组件，提供统一的侧边栏、header、footer，支持侧边栏折叠。
- **AdminListPage**: 通用的资源列表页 LiveComponent，支持分页、排序、搜索。
- **AdminFormPage**: 通用的资源表单页 LiveComponent，支持创建和编辑。
- **AdminManager**: Resource 和 Page 的注册管理器，提供统一的资源访问接口。
- **AdminResource 属性**: 标记 Resource 类，定义资源配置（名称、模型、标题、图标）。

## 依赖注入 (DI)

框架使用 `php-di/php-di` 实现依赖注入：

```php
// 自动装配
class UserController {
    public function __construct(
        private UserRepository $users
    ) {}
}

// 手动获取
$user = $app->make(User::class);

// 绑定
$app->instance(Interface::class, Implementation::class);

// 单例
$app->singleton(Service::class);
```

## 辅助函数

```php
// 应用
app();                              // Application 实例
config('app.name');                  // 配置值
env('APP_ENV', 'local');            // 环境变量

// 数据库
db();                               // Connection 实例

// 认证
auth();                             // AuthManager 实例
user();                             // 当前用户模型

// 路径
base_path('config/app.php');        // 项目根目录
storage_path('logs/app.log');       // storage 目录
public_path('index.php');           // public 目录

// URL
asset('css/style.css');             // 资源 URL
media_url('images/photo.jpg');      // 媒体 URL
download_url('files/doc.pdf');      // 下载 URL
stream_url('videos/movie.mp4');     // 流式 URL

// 其他
redirect('/dashboard');             // 重定向响应
abort(404, 'Not Found');            // 抛出 HTTP 异常
now();                              // DateTimeImmutable
today();                            // 今日 DateTimeImmutable
```
