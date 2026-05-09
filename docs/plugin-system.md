# 插件系统文档

> 框架的插件机制：发现、加载、启停、生命周期。当前为初始版本，核心结构已建立，功能仍在完善中。

---

## 目录

1. [架构概览](#1-架构概览)
2. [PluginInterface](#2-plugininterface)
3. [Plugin 基类](#3-plugin-基类)
4. [PluginManager](#4-pluginmanager)
5. [PluginServiceProvider](#5-pluginserviceprovider)
6. [插件目录结构](#6-插件目录结构)
7. [插件生命周期](#7-插件生命周期)
8. [创建插件](#8-创建插件)
9. [当前状态与规划](#9-当前状态与规划)

---

## 1. 架构概览

```
plugins/                           插件根目录
  ├── my-plugin/                   单个插件目录
  │   ├── plugin.json              插件元数据
  │   └── Plugin.php               插件入口类
  │
PluginServiceProvider              服务提供者
  └── PluginManager                插件管理器
      ├── scan()                   扫描发现插件
      ├── boot($enabledNames)      启用插件
      └── loadPlugin($meta)        加载插件实例

Admin\Models\PluginSetting         插件启用状态（数据库）
```

---

## 2. PluginInterface

```php
interface PluginInterface
{
    public function getName(): string;          // 插件标识
    public function getTitle(): string;         // 显示名称
    public function getDescription(): string;   // 描述
    public function getVersion(): string;       // 版本号
    public function boot(): void;               // 启动逻辑
}
```

---

## 3. Plugin 基类

```php
abstract class Plugin implements PluginInterface
{
    protected string $name;
    protected string $title;
    protected string $description;
    protected string $version;

    public function getName(): string { return $this->name; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getVersion(): string { return $this->version; }
}
```

---

## 4. PluginManager

### 4.1 核心方法

| 方法 | 说明 |
|------|------|
| `scan()` | 扫描插件目录，返回发现的插件列表 |
| `getPlugins()` | 获取所有已发现的插件元数据 |
| `getPlugin($name)` | 获取指定插件的元数据 |
| `boot($enabledNames)` | 启用指定插件列表 |
| `getInstance($name)` | 获取已启动的插件实例 |
| `getInstances()` | 获取所有已启动的插件实例 |

### 4.2 scan() — 发现插件

```php
public function scan(): array
{
    // 遍历 plugins/ 目录
    // 每个子目录检查 plugin.json
    // 解析元数据，检测入口类
    // 返回 [name => meta] 数组
}
```

扫描规则：
1. 遍历 `plugins/` 下所有子目录
2. 检查 `plugin.json` 是否存在
3. 解析 JSON，必须有 `name` 字段
4. 自动检测 `Plugin.php` 中的类名

### 4.3 boot() — 启用插件

```php
public function boot(array $enabledNames): void
{
    $this->scan();

    foreach ($enabledNames as $name) {
        $instance = $this->loadPlugin($meta);
        Hook::fire('plugin.boot', [$instance]);
        $instance->boot();
        Hook::fire('plugin.booted', [$instance]);
        $this->pluginInstances[$name] = $instance;
    }
}
```

启动流程：
1. 扫描所有插件
2. 遍历启用的插件名
3. 加载 Plugin.php 文件
4. 实例化插件类
5. 触发 `plugin.boot` Hook
6. 调用 `boot()` 方法
7. 触发 `plugin.booted` Hook
8. 错误时记录日志，不中断其他插件

### 4.4 loadPlugin() — 加载插件实例

```php
protected function loadPlugin(array $meta): ?PluginInterface
{
    require_once $meta['path'] . '/Plugin.php';

    $class = $meta['class'];
    $instance = new $class();

    if (!$instance instanceof PluginInterface) {
        return null;
    }

    return $instance;
}
```

### 4.5 detectPluginClass() — 自动检测类名

```php
protected function detectPluginClass(string $pluginDir, string $name): string
{
    // 通过 token_get_all() 解析 Plugin.php
    // 提取 namespace 和 class 名
    // 返回完整类名（如 \MyPlugin\Plugin）
}
```

---

## 5. PluginServiceProvider

```php
class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, function () {
            return new PluginManager($this->app->basePath() . '/plugins');
        });
    }

    public function boot(): void
    {
        Hook::addAction('app.booted', function () {
            $manager = $this->app->make(PluginManager::class);

            // 从数据库读取启用的插件
            $enabled = array_column(
                PluginSetting::where('enabled', true)->get()->all(),
                'name'
            );

            $manager->boot($enabled);
        }, 0, 0);
    }
}
```

**要点**：
- 插件在 `app.booted` 之后才启动（确保所有服务已注册）
- 启用状态从 `plugin_settings` 数据库表读取
- 数据库表不存在时静默忽略（迁移前）

---

## 6. 插件目录结构

```
plugins/
  └── my-plugin/                    # 插件目录（名称随意）
      ├── plugin.json               # 元数据（必需）
      └── Plugin.php                # 入口类（必需）
```

### 6.1 plugin.json 格式

```json
{
    "name": "my-plugin",
    "title": "我的插件",
    "description": "一个示例插件",
    "version": "1.0.0"
}
```

| 字段 | 必需 | 说明 |
|------|------|------|
| `name` | ✅ | 插件唯一标识 |
| `title` | ❌ | 显示名称（默认 = name） |
| `description` | ❌ | 描述（默认空） |
| `version` | ❌ | 版本号（默认 1.0.0） |
| `class` | ❌ | 入口类名（自动检测） |

### 6.2 Plugin.php 格式

```php
namespace MyPlugin;

use Framework\Plugin\Plugin;

class Plugin extends Plugin
{
    protected string $name = 'my-plugin';
    protected string $title = '我的插件';
    protected string $description = '一个示例插件';
    protected string $version = '1.0.0';

    public function boot(): void
    {
        // 注册路由、中间件、事件监听等
    }
}
```

---

## 7. 插件生命周期

```
应用启动
  │
  ├── ServiceProvider::register()
  │   └── 注册 PluginManager 单例
  │
  ├── app.booted Hook
  │   ├── 从数据库读取启用的插件名
  │   └── PluginManager::boot($enabledNames)
  │       ├── scan() → 发现所有插件
  │       └── 遍历启用的插件
  │           ├── loadPlugin() → 加载 Plugin.php
  │           ├── Hook::fire('plugin.boot')
  │           ├── $instance->boot()
  │           └── Hook::fire('plugin.booted')
  │
  └── 应用运行中
      └── PluginManager::getInstance($name) → 获取插件实例
```

### Hook 节点

| Hook | 参数 | 说明 |
|------|------|------|
| `plugin.boot` | `[$instance]` | 插件启动前 |
| `plugin.booted` | `[$instance]` | 插件启动后 |

---

## 8. 创建插件

### 步骤一：创建目录

```bash
mkdir -p plugins/my-plugin
```

### 步骤二：创建 plugin.json

```json
{
    "name": "my-plugin",
    "title": "我的插件",
    "description": "示例插件",
    "version": "1.0.0"
}
```

### 步骤三：创建 Plugin.php

```php
namespace MyPlugin;

use Framework\Plugin\Plugin;
use Framework\Events\Hook;
use Admin\Services\AdminManager;

class Plugin extends Plugin
{
    protected string $name = 'my-plugin';
    protected string $title = '我的插件';
    protected string $description = '示例插件';
    protected string $version = '1.0.0';

    public function boot(): void
    {
        // 注册 Admin Resource
        // AdminManager::registerResource(MyResource::class);

        // 注册 Admin Page
        // AdminManager::registerPage(MyPage::class);

        // 监听事件
        // Hook::on('user.created', function ($user) { ... });
    }
}
```

### 步骤四：在数据库中启用

```php
// 在 Admin 后台的插件管理页面启用
// 或手动插入
PluginSetting::create(['name' => 'my-plugin', 'enabled' => true]);
```

---

## 9. 当前状态与规划

### 已完成

- ✅ 插件目录扫描与发现
- ✅ plugin.json 元数据解析
- ✅ Plugin.php 入口类自动检测
- ✅ 数据库驱动的启用/禁用
- ✅ Hook 事件集成
- ✅ 错误容忍（单个插件失败不影响其他）

### 待完善

- ⬜ 插件安装/卸载流程
- ⬜ 插件依赖管理
- ⬜ 插件配置界面
- ⬜ 插件路由自动注册
- ⬜ 插件数据库迁移支持
- ⬜ 插件静态资源管理
- ⬜ 插件更新机制
- ⬜ Admin 插件管理页面
- ⬜ 插件权限控制
- ⬜ 插件间通信机制

### 设计方向

插件系统采用**最小侵入**设计：
- 插件通过 `boot()` 方法注册自己的 Resource、Page、Hook
- 不修改框架核心代码
- 通过 Hook 系统与其他模块交互
- 通过 AdminManager 注册 Admin 界面
