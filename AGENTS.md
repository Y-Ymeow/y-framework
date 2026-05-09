# AI 开发指南 (AGENTS.md)

本文档为 AI 助手提供项目开发指南。

---

## 1. 项目概述

这是一个全栈 PHP 框架，采用现代化的架构设计：

- **后端**: 类 Laravel 的完整框架，包含 ORM、路由、认证、缓存、队列等
- **前端**: 自研的响应式系统 (y-directive + y-live + y-ux)
- **构建**: Vite + Bun 的前端构建系统

---

## 2. 核心模块

| 模块 | 路径 | 说明 |
|------|------|------|
| Foundation | `framework/Foundation/` | Application, Container, Kernel |
| HTTP | `framework/HTTP/` | Request, Response, Session, Middleware |
| Routing | `framework/Routing/` | 路由系统 |
| View | `framework/View/` | Element, Document |
| Component/Live | `framework/Component/Live/` | LiveComponent |
| UX | `framework/UX/` | 组件库 |
| Database | `framework/Database/` | ORM |
| Auth | `framework/Auth/` | 认证 |
| Events | `framework/Events/` | 事件系统 |

---

## 3. 开发流程

1. **理解需求**: 阅读功能描述，理解业务逻辑
2. **搜索代码**: 使用 glob/grep 搜索现有实现，遵循既有模式
3. **编写代码**: 遵循本项目的代码风格和约定
4. **验证**: 运行测试（若有），检查语法错误

---

## 4. 注意事项

- 修改 `resources/js/` 后执行 `cd resources/js && npm run build` 或 `bun run build`
- UX 组件样式以 `.ux-` 为前缀
- LiveComponent 的 public 属性与前端 state 同步
- 使用 Element 构建 UI，避免字符串拼接 HTML
- 遵循既有的命名约定和代码风格