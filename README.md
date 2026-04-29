# ymeow/php-framework

一个基于 PHP 8.4 的轻量级、组件驱动、路由优先的现代 PHP 框架。

## 特性

- **路由优先 (Route-First)**：通过 PHP 8 Attribute 将路由直接定义在控制器方法上，消除 `routes.php` 大文件。
- **组件驱动 (Component-Driven)**：页面由 LiveComponent 组成，服务端驱动响应式交互，无需手写 JavaScript。
- **Element 链式 UI**：通过 `Element::make()` 链式调用构建类型安全的 UI，不写 HTML 模板。
- **分片更新 (Fragment Update)**：精准的局部 DOM 刷新，比全量 Patch 更轻量。
- **Admin 自动生成**：基于 Resource 声明，自动生成 CRUD 列表页、表单页及侧边栏布局。
- **轻量前端 (Y-UI)**：~20KB 的前端指令引擎，支持 `data-text`、`data-show`、`data-model`、`data-effect` 等响应式指令。
- **编译驱动**：路由、LiveAction 映射全面支持预编译与缓存，消除运行时反射开销。
- **安全优先**：状态校验和（Checksum）+ 会话绑定签名，防止状态篡改与跨用户伪造。

## 快速开始

1. **安装依赖**
   ```bash
   composer install
   npm install
   ```

2. **环境配置**
   ```bash
   cp .env.example .env
   php bin/console key:generate
   ```

3. **数据库迁移**
   ```bash
   php bin/console migrate
   ```

4. **启动开发服务器**
   ```bash
   npm run dev          # 启动 Vite（前端 HMR）
   composer serve       # 启动 PHP 内置服务器
   ```

## 文档

详细说明请查看 `docs/` 目录：

### 入门
- [开发者指南 (GUIDE.md)](docs/GUIDE.md) — 从零到一的开发全流程
- [架构设计 (architecture.md)](docs/architecture.md) — 设计哲学与核心模块
- [生命周期 (lifecycle.md)](docs/lifecycle.md) — Hook 系统与 Action/Filter

### 功能模块
- [路由系统 (routing.md)](docs/routing.md) — Attribute 路由定义
- [数据库系统 (database.md)](docs/database.md) — QueryBuilder、ORM Model、迁移
- [认证系统 (auth.md)](docs/auth.md) — Session 认证、Remember Me、权限管理
- [存储系统 (storage.md)](docs/storage.md) — 文件管理、图片处理、断点续传

### 前端与交互
- [Y-UI 前端框架 (frontend.md)](docs/frontend.md) — 响应式指令系统
- [Live Component (live-component.md)](docs/live-component.md) — 服务端驱动组件
- [组件间通信 (live-cross-component-events.md)](docs/live-cross-component-events.md)
- [CSS 引擎 (css-engine.md)](docs/css-engine.md)
- [UI 组件库 (ui-components.md)](docs/ui-components.md) — Element 基础组件
- [UX 组件库 (ux-components.md)](docs/ux-components.md) — DataTable、Modal、Tabs 等

## 常用命令

| 命令 | 说明 |
|------|------|
| `php bin/console route:list` | 列出所有注册路由 |
| `php bin/console route:cache` | 编译路由缓存 |
| `php bin/console live:cache` | 编译 LiveAction 映射缓存 |
| `php bin/console migrate` | 执行数据库迁移 |
| `php bin/console migrate:rollback` | 回滚迁移 |
| `php bin/console make:component {Name}` | 创建 LiveComponent |
| `php bin/console make:migration {Name}` | 创建迁移文件 |

## 核心哲学

- **路由优先**：让路由与控制器代码紧密结合，提升可维护性。
- **组件驱动**：交互逻辑内聚在组件中，拒绝散落的 JS 碎片。
- **性能第一**：编译驱动，每一毫秒的运行时解析都是浪费。
- **轻量透明**：无强制依赖，可看到每一行 SQL 和每一个输出。
