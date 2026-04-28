# ymeow/php-framework 文档中心

欢迎来到 `ymeow/php-framework` 的开发文档。这是一个追求极致轻量、反 MVC 架构、路由优先且组件驱动的现代化 PHP 框架。

## 🚀 快速开始

1. **安装依赖**: `composer install`
2. **环境配置**: 复制 `.env.example` 为 `.env` 并配置数据库。
3. **初始化**: `php bin/console key:generate`
4. **运行迁移**: `php bin/console migrate`
5. **启动服务**: `composer serve` 或 `php -S 127.0.0.1:8000 -t public public/index.php`

## 📖 文档目录

### 核心架构
- [架构设计 (architecture.md)](architecture.md) - 设计哲学、核心模块与 DI 系统。
- [生命周期与 Hook (lifecycle.md)](lifecycle.md) - 生命周期管理与 Action/Filter 系统。
- [开发者指南 (GUIDE.md)](GUIDE.md) - 从零到一的开发全流程指南。

### 功能模块
- [路由系统 (routing.md)](routing.md) - 基于 Attribute 的路由定义与管理。
- [数据库系统 (database.md)](database.md) - QueryBuilder, ORM Model 与迁移系统。
- [认证系统 (auth.md)](auth.md) - 用户认证、Session 及角色权限管理。
- [存储系统 (storage.md)](storage.md) - 文件存储与资源管理。

### 前端与交互
- [前端框架 Y-UI (frontend.md)](frontend.md) - 轻量级响应式前端状态管理与指令。
- [Live Component (live-component.md)](live-component.md) - 服务端驱动的响应式交互组件。
- [组件间通信 (live-cross-component-events.md)](live-cross-component-events.md) - LiveComponent 的事件同步。
- [CSS 引擎 (css-engine.md)](css-engine.md) - 动态样式处理。
- [UI 组件库 (ui-components.md)](ui-components.md) - 基于 Tailwind 的 UI 基础组件。
- [UX 组件库 (ux-components.md)](ux-components.md) - 交互式 UX 组件。

## 🛠 常用命令速查

| 命令 | 说明 |
|------|------|
| `php bin/console route:list` | 列出所有注册路由 |
| `php bin/console route:cache` | 编译路由缓存 |
| `php bin/console live:cache` | 编译组件 Action 缓存 |
| `php bin/console migrate` | 执行数据库迁移 |
| `php bin/console make:component {Name}` | 创建新 LiveComponent |
| `php bin/console make:migration {Name}` | 创建新迁移文件 |
