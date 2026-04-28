# 框架开发指南 (AGENT.md)

本文件定义了该 PHP 框架的核心开发模式与工程标准，开发时必须严格遵守。

## 1. 核心架构原则

### 1.1 视图系统 (View System)
- **一切皆元素**: 优先使用 `Framework\View\Base\Element` 及其子类（如 `Container`, `Text`）构建 UI，避免原始 HTML 字符串拼接。
- **自动包装**: `Response::html()` 具备自动检测能力。如果返回的内容不含 `<html>`，框架会自动使用 `Document` 进行标准化包装（注入 Head, Assets, CSRF 等）。
- **静态配置**: 使用 `Document::setTitle()`, `Document::uxStatic()` 等静态方法在路由层面配置页面元数据。

### 1.2 Live 系统 (y-ui)
- **状态同步**: `LiveComponent` 的公共属性（`public`）会自动与前端 `state` 同步。
- **指令优先**: 优先通过 `$this->toast()`, `$this->openModal()` 等内置方法与前端交互。
- **通用集成**: 使用 `$this->ux($component, $id, $action, $data)` 发送精准组件指令。

## 2. 局部更新最佳实践 (Fragment Update)

严禁在 Action 中默认返回全量 HTML。应使用“分片模式”实现精准更新：

1. **定义分片**: 在 `render()` 中，对需要局部更新的元素调用 `->liveFragment('name')`。
2. **触发刷新**: 在 `LiveAction` 方法中，调用 `$this->refresh('name')`。
3. **工作原理**: 框架会自动捕获对应元素的渲染输出并作为 `fragments` 补丁发送给前端。

## 3. UX 组件规范

- **组件位置**: `src/UX/` 目录下按功能分类。
- **Live 绑定**: 组件必须支持 `liveAction()` 或 `liveModel()` 以实现与服务器端的联动。
- **CSS 隔离**: 所有 UX 样式必须以 `.ux-` 为前缀，并确保点击等交互不触发布局抖动（使用 `box-sizing: border-box` 和锁定 `width`）。

## 4. 资产管理 (Asset Management)

- **Vite 继承**: 始终使用全局 `vite('path/to/source')` 助手函数加载 JS/CSS，严禁硬编码编译后的路径。
- **核心资源**: `Document` 渲染时会自动加载 `AssetRegistry::core()`，包括 CSS 引擎和基础 y-ui 库。

## 5. 数据库模式

- **健壮性**: `Model` 基类具备容器感知能力。如果连接未初始化，它会尝试从 `Application` 容器中自动找回。
- **关系**: 优先使用声明式关系（`hasMany`, `belongsTo`）。

## 6. 注意事项 (Pitfalls)

- **序列化**: `LiveComponent` 的 `operations` 属性已被排除在序列化之外，确保指令不会在多次请求间累加。
- **JS 编译**: 修改 `resources/js/` 下的内容后，必须执行 `./scripts/build-js.sh` 才能生效。
- **事件委托**: `ux.js` 必须使用全局事件委托，以确保在 DOM Patch 发生后交互逻辑依然有效。
