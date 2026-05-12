# AI 开发指南 (AGENTS.md)

本文档为 AI 助手提供项目开发指南。

---

## 1. 项目概述

这是一个全栈 PHP 框架，采用现代化的架构设计：

- **后端**：类 Laravel 的完整框架，包含 ORM、路由、认证、缓存、队列等
- **前端**：自研的响应式系统 (y-directive + y-live + y-ux)
- **构建**：Vite + Bun 的前端构建系统

---

## 2. 核心模块速查表

| 模块 | 路径 | 核心类/文件 |
|------|------|------------|
| Foundation | `framework/Foundation/` | Application, Container, Kernel |
| HTTP | `framework/HTTP/` | Request, Response, Session, Middleware |
| Routing | `framework/Routing/` | `#[Route]`, `#[RouteGroup]`, `#[Middleware]` |
| View | `framework/View/` | Element, Document, AssetRegistry |
| Live | `framework/Component/Live/` | AbstractLiveComponent, LiveResponse, `#[LiveAction]` |
| UX | `framework/UX/` | UXComponent, DataTable, FormBuilder, Modal, `#[Prop]`, `#[State]` |
| Database | `framework/Database/` | Model, Query Builder, Schema, `HasTranslations` |
| Auth | `framework/Auth/` | Auth 系统 |
| Events | `framework/Events/` | 事件系统 |
| Install | `framework/Install/` | InstallManager, InstallController |
| 前端 | `resources/js/` | y-directive, y-live, y-ux |
| 入口 | `resources/js/ui.js` | y-directive + y-live |
| 入口 | `resources/js/ux.js` | y-ux 组件 |

---

## 3. 开发流程

1. **理解需求**：阅读功能描述，理解业务逻辑
2. **搜索代码**：使用 glob/grep 搜索现有实现，遵循既有模式
3. **编写代码**：遵循本项目的代码风格和约定
4. **验证**：运行测试（若有），检查语法错误

---

## 4. 开发约定

### UI 构建
- 使用 `Element::make()`, `Container`, `Text` 等构建 UI
- 避免字符串拼接 HTML
- 链式调用：`$el->id()->class()->text()->child()`

### LiveComponent
- `#[State]` 定义组件状态，自动与前端同步
- `#[LiveAction]` 定义可被前端调用的方法
- 使用 `LiveResponse::make()` 返回响应
- 优先使用 `$this->toast()`, `$this->openModal()` 等内置方法

### 分片更新
- 在 `render()` 中标记：`$el->liveFragment('name')`
- Action 中返回：`LiveResponse::make()->fragment('name', $html)`

### UX 组件
- 组件样式以 `.ux-` 为前缀
- 支持 `->liveAction()` 和 `->liveModel()` 与 Live 系统集成

### 前端
- 修改 `resources/js/` 后执行 `cd resources/js && npm run build`
- y-ux 使用全局事件委托确保 DOM Patch 后交互有效
- 指令执行使用 `L.executeOperation()` 而非直接导入

### 数据库
- 使用 `HasTranslations` trait 实现多语言字段
- 迁移命令：`php bin/console migrate`
- 种子命令：`php bin/console db:seed`

### 路由
- 使用 `#[Route]` 属性定义路由
- 使用 `#[RouteGroup]` 定义路由组前缀
- 使用 `#[Middleware]` 绑定中间件
- 缓存命令：`php bin/console route:cache`

### 安装系统
- `.env` 不存在或 `APP_KEY` 为空时，`Kernel::handle()` 自动拦截并重定向到 `/install`
- 6 步安装向导（Element 渲染，非 Live 模式，使用隐藏字段传递步骤数据）
- 安装后访问 `/install` 自动重定向到首页
- 核心检测：`InstallManager::isInstalled()`

---

## 5. 常用命令

```bash
# 前端构建
cd resources/js && npm run build

# 数据库
php bin/console migrate
php bin/console db:seed

# 路由
php bin/console route:cache
php bin/console cache:clear route
```