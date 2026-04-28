# 更新日志

## 2026-04-28 - 国际化支持 (Intl) 和路由重构

### 新增功能

#### 1. 国际化系统 (Intl)

**后端支持**
- `Element::intl($key)` 方法：标记元素需要翻译
- `Translator::getMany()` 方法：批量获取翻译
- `LiveComponentResolver` 新增 `POST /live/intl` 路由：处理语言切换请求

**前端支持**
- 新增 `resources/js/y-live/intl.js` 模块
- `window.$locale` 全局函数：切换语言
- `data-intl` 属性：标记需翻译元素，服务端自动替换
- 指令系统注入 `$locale` 变量：可在 `data-on:*` 等指令中使用

**使用方式**
```php
// 组件中使用
Element::make('span')->intl('messages.welcome');

// 或模板中直接写
<span data-intl="messages.welcome"></span>
```

```html
<!-- 切换语言 -->
<button data-on:click="$locale('zh')">中文</button>
<button data-on:click="$locale('en')">English</button>
```

#### 2. 路由重构

**Live Component 路由**
- `POST /live` → `POST /live/update`
- `POST /navigate` → `POST /live/navigate`
- 新增 `POST /live/intl` 用于语言切换

**文件修改**
- `src/Component/LiveComponentResolver.php`: 更新路由定义
- `resources/js/y-live/core/connection.js`: `/live` → `/live/update`
- `resources/js/y-live/navigate.js`: `/navigate` → `/live/navigate`

#### 3. 指令系统增强

- `directives.js` 的 `evaluate()` 和 `execute()` 注入 `$locale` 参数
- `$locale` 可在表达式中访问当前语言：`$locale === 'zh' ? '你好' : 'Hello'`

### 安全改进

- 所有 `onclick` 等内联事件处理器已在 `Element::render()` 和 `dom.js` 中被完全禁用
- `data-intl` 属性已加入 `LIVE_SAFE_DATA_ATTRS` 白名单
- 语言切换通过指令系统调用，无 XSS 风险

### 文档更新

- `docs/live-component.md`: 新增国际化章节，更新路由说明
- `docs/frontend.md`: 新增 `$locale` 魔法变量，新增 i18n 章节，更新路由
- `docs/CHANGELOG.md`: 本文档

## 2024-01-XX - Admin 系统和核心功能增强

### 新增功能

#### 1. Admin 后台管理系统

**完整的后台布局**
- `AdminLayout` 组件：提供统一的侧边栏、header、footer
- 侧边栏动态折叠：使用 `data-effect` 实现平滑过渡动画
- 状态持久化：localStorage 自动保存侧边栏状态
- 响应式设计：支持移动端和桌面端

**Resource 系统**
- `AdminResourceController`：通用的 CRUD 控制器
- `AdminListPage`：列表页 LiveComponent（分页、排序、搜索）
- `AdminFormPage`：表单页 LiveComponent（创建、编辑）
- `AdminManager`：Resource 和 Page 的注册管理
- `#[AdminResource]` 属性：标记 Resource 类

**Data 组件库**
- `DataTable`：数据表格（条纹、边框、悬停、固定列、分页）
- `DataList`：数据列表
- `DataGrid`：响应式网格布局
- `DataCard`：卡片式数据展示
- `DataTree`：树形数据结构
- `DescriptionList`：描述列表

#### 2. 数据库增强

**SQLite 支持**
- `Connection::resolveSqlitePath()`：自动处理 SQLite 相对路径
- `Connection::getDriverName()`：获取当前数据库驱动
- Schema Builder 多数据库支持：根据驱动类型生成对应的 SQL

**QueryBuilder 改进**
- 聚合方法状态保护：`count()`, `sum()`, `avg()`, `max()`, `min()` 执行后恢复原始 selects
- 修复空数据显示问题：聚合查询不再污染后续查询

**迁移系统**
- SQLite 兼容性：`migrations` 表结构适配 SQLite
- Schema Builder SQLite 语法支持：
  - `INTEGER PRIMARY KEY AUTOINCREMENT`
  - `UNIQUE` 约束内联定义
  - 双引号标识符
  - `PRAGMA table_info` 替代 `information_schema`

#### 3. LiveComponent 增强

**data-effect 动态效果**
- 响应式副作用：状态变化时自动执行 JavaScript 代码
- `$.property` 访问组件状态
- 支持全局 API：`document`、`localStorage`、`navigator` 等
- 典型应用：侧边栏折叠、主题切换、状态持久化

#### 4. 队列系统

**队列驱动**
- `SyncDriver`：同步执行（开发/测试）
- `DatabaseDriver`：数据库持久化（生产环境）
- `RedisDriver`：Redis 列表（高性能场景）

**队列管理**
- `QueueManager`：静态入口，统一管理队列
- `QueueWorkerRoute`：HTTP Worker，通过 `POST /_queue/worker` 处理任务
- `queue()` 辅助函数：快速推送任务

**DemoJob 示例**
- 可运行的队列任务示例
- 日志记录功能
- 失败处理回调

### 架构变更

#### 1. 路由系统

**Resource 路由**
- 通配符路由：`/admin/{resource}` 匹配所有 Resource
- `AdminResourceController` 统一处理 CRUD
- Resource 类只配置表单/表格，不作为路由处理器
- 支持自定义路由（直接定义在 Resource 类中）

**扫描顺序优化**
- `config/routes.php` 配置扫描目录优先级
- `src/Admin` 优先于 `admin/Pages`
- 避免路由冲突

#### 2. 服务提供者

**AdminServiceProvider**
- 自动扫描 `admin/Resources` 目录
- 注册 Resource 到 `AdminManager`
- 不注册路由（由 `AdminResourceController` 统一处理）

**Kernel 改进**
- 构造函数中立即注册 Router 到容器
- 确保 ServiceProviders 获取同一个 Router 实例
- 移除冗余的 `instance()` 和 `boot()` 调用

#### 3. 控制台命令

**命令增强**
- `RouteListCommand`：启动 ServiceProviders，显示所有路由（包括 ServiceProvider 注册的）
- `MigrateCommand`：启动 ServiceProviders，支持 SQLite 数据库

### 样式和 UI

#### 1. CSS 样式

**Admin 布局样式** (`ux.css`)
- `.admin-sidebar`：固定左侧边栏，260px 宽度
- `.admin-sidebar-collapsed`：折叠状态 64px
- `.admin-main`：主内容区，自动调整 margin
- `.admin-header`：顶部 header，包含切换按钮和用户信息
- `.admin-footer`：底部版权信息
- CSS transition：0.2s 平滑过渡动画

**Data 组件样式** (`data.css` 已合并到 `ux.css`)
- DataTable：条纹、边框、悬停、固定列、空状态
- DataList：分割线、边框、悬停效果
- DataGrid：响应式列、卡片式布局
- DataCard：封面、头像、字段展示
- DataTree：嵌套、展开/折叠、复选框

#### 2. 组件改进

**Router 修复**
- 移除 `scan()` 的非空守卫：允许在有已有路由时继续扫描
- 修复 handler 为对象实例时的字符串转换问题
- 数组 handler 支持：`[new QueueWorkerRoute(), 'handle']`

**Container 修复**
- Router 实例一致性：确保 ServiceProviders 和 Kernel 使用同一个实例
- 移除冗余的 `instance(Router::class)` 调用

### 配置变更

#### 1. 环境变量

```env
# 数据库
DB_CONNECTION=sqlite
DB_DATABASE=database/demo-live.sqlite

# 队列
QUEUE_CONNECTION=database
QUEUE_TOKEN=dev-test-token
```

#### 2. 路由配置

```php
// config/routes.php
'routes' => [
    'src/Admin',        // Admin 控制器（优先）
    'admin/Pages',      // Admin 页面
    'admin/Resources',  // Admin Resource
    'app/Controllers',  // 应用控制器
],
```

#### 3. 服务提供者

```php
// config/app.php
'providers' => [
    // ...
    \Framework\Admin\AdminServiceProvider::class,  // 新增
],
```

### Bug 修复

1. **路由丢失**：ServiceProviders 注册的路由在 `route:list` 中不可见
2. **Router 实例不一致**：Kernel 和 ServiceProviders 使用不同的 Router 实例
3. **SQLite 语法错误**：Schema Builder 硬编码 MySQL 语法
4. **聚合查询污染**：`count()` 等方法修改 selects 后未恢复
5. **空数据显示错误**：`QueryBuilder::get()` 返回空数组时显示空行
6. **Handler 对象转换错误**：`[new Class(), 'method']` 无法转字符串
7. **MigrateCommand 失败**：未启动 ServiceProviders，无法创建 Connection

### 文档更新

- `docs/routing.md`：Admin 路由系统说明
- `docs/architecture.md`：Admin 架构和 Data 组件
- `docs/live-component.md`：data-effect 动态效果
- `docs/CHANGELOG.md`：本文档

### 迁移指南

#### 从旧版本升级

1. **更新配置**
   ```bash
   # 添加 AdminServiceProvider
   # 更新 routes.php 扫描顺序
   # 设置 QUEUE_TOKEN
   ```

2. **数据库迁移**
   ```bash
   php bin/console migrate
   ```

3. **清除缓存**
   ```bash
   rm -rf storage/cache/*
   ```

4. **检查 Resource 定义**
   - 确保 Resource 类实现 `ResourceInterface`
   - 移除 Resource 类中的路由定义（由 `AdminResourceController` 统一处理）

### 已知问题

无

### 未来计划

- [ ] Admin 权限系统
- [ ] 批量操作支持
- [ ] 导出/导入功能
- [ ] 更多 Data 组件（图表、统计卡片）
- [ ] 多队列 Worker 支持
- [ ] 队列任务优先级
- [ ] 计划任务 Dashboard
